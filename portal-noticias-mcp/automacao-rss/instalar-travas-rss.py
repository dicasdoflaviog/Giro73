"""Install reviewed RSS worker on its existing VPS, with locked backup and no publishing."""
import base64
import fcntl
import grp
import hashlib
import json
import os
from pathlib import Path
import py_compile
import pwd
import shutil
import subprocess
import tempfile
import time

BASELINE = '3eebe39dc47bb5ce70f7df6f5baf127ab536eef19f0443539d13250d1670015e'

def safe_cron_inventory():
    found=[]
    paths=[Path('/etc/crontab')]
    if Path('/etc/cron.d').is_dir():paths += list(Path('/etc/cron.d').iterdir())
    texts=[]
    for p in paths:
        if p.is_file():
            try:texts.append((str(p),p.read_text(errors='replace')))
            except OSError:pass
    for user in ['root','giro73-rss']:
        r=subprocess.run(['crontab','-u',user,'-l'],capture_output=True,text=True,check=False)
        if r.returncode==0:texts.append(('crontab:'+user,r.stdout))
    for origin,text in texts:
        for raw in text.splitlines():
            line=raw.strip()
            if line and not line.startswith('#') and ('/opt/giro73-rss/run' in line or 'coletor_rss.py' in line):
                fields=line.split();found.append({'origin':origin,'schedule':' '.join(fields[:1 if line.startswith('@') else 5])})
    return found

def atomic_write(path,data,uid,gid,mode):
    fd,name=tempfile.mkstemp(prefix='.g73-policy-',dir=path.parent)
    try:
        with os.fdopen(fd,'wb') as f:f.write(data);f.flush();os.fsync(f.fileno())
        os.chown(name,uid,gid);os.chmod(name,mode);os.replace(name,path)
    finally:
        if os.path.exists(name):os.unlink(name)

def apply(bundle):
    if os.geteuid()!=0:raise RuntimeError('Execute com sudo/root na VPS do coletor.')
    root=Path('/opt/giro73-rss');worker=root/'coletor_rss.py';config=Path('/etc/giro73-rss.json');python=root/'.venv/bin/python'
    if not all(p.is_file() for p in [worker,config,python]):raise RuntimeError('Instalação esperada do coletor não encontrada; nada foi alterado.')
    code=__import__('gzip').decompress(base64.b64decode(bundle['worker_gzip_base64']))
    if hashlib.sha256(code).hexdigest()!=bundle['worker_sha256']:raise RuntimeError('Hash do coletor não confere.')
    before=worker.read_bytes();beforeconfig=config.read_bytes();oldhash=hashlib.sha256(before).hexdigest()
    if oldhash not in [BASELINE,bundle['worker_sha256']]:
        raise RuntimeError('VPS possui versão diferente; atualização interrompida para preservar mudanças. SHA256 atual: '+oldhash)
    cron=safe_cron_inventory()
    print('Agendamento encontrado:',json.dumps(cron,ensure_ascii=False))
    if not cron:raise RuntimeError('Cron do coletor não localizado; nenhuma alteração realizada.')
    state=Path('/var/lib/giro73-rss/vistos_rss.sqlite3.lock')
    if not state.exists():
        try:
            fd=os.open(state,os.O_CREAT|os.O_EXCL|os.O_RDWR,0o600)
            os.fchown(fd,pwd.getpwnam('giro73-rss').pw_uid,grp.getgrnam('giro73-rss').gr_gid);os.close(fd)
        except FileExistsError:
            pass
    with state.open('a+') as lock:
        deadline=time.monotonic()+30
        while True:
            try:fcntl.flock(lock,fcntl.LOCK_EX|fcntl.LOCK_NB);break
            except BlockingIOError:
                if time.monotonic()>deadline:raise RuntimeError('Coleta em andamento; tente novamente após terminar. Nada alterado.')
                time.sleep(.25)
        group=grp.getgrnam('giro73-rss').gr_gid
        stage=Path(tempfile.mkdtemp(prefix='.policy-stage-',dir=root));os.chown(stage,0,group);os.chmod(stage,0o750)
        changed=False;backup=None
        try:
            candidate=stage/'coletor_rss.py';candidate.write_bytes(code);os.chown(candidate,0,group);os.chmod(candidate,0o640)
            py_compile.compile(str(candidate),doraise=True)
            runner=shutil.which('sudo')
            if not runner:raise RuntimeError('sudo ausente; nada foi alterado.')
            def test(flag):
                result=subprocess.run([runner,'-u','giro73-rss',str(python),str(candidate),flag],capture_output=True,text=True,timeout=180)
                # Only reviewed self-test output is printed. Never echo provider responses or environment.
                for line in result.stdout.splitlines():
                    if line.startswith(('PASS ','RSS_')):print(line)
                if result.returncode:raise RuntimeError('Validação '+flag+' falhou; nenhuma publicação foi solicitada.')
            test('--policy-self-test');test('--policy-check-server')
            data=json.loads(beforeconfig.decode('utf-8-sig'));feeds=data.get('feeds')
            if not isinstance(feeds,list):raise RuntimeError('Configuração de feeds inválida.')
            import importlib.util
            spec=importlib.util.spec_from_file_location('g73_policy_candidate',candidate);module=importlib.util.module_from_spec(spec);spec.loader.exec_module(module)
            counts={'draft':0,'auto':0}
            for source in feeds:
                source['max_age_hours']=24;source['auto_max_age_hours']=24;source['allow_undated']=False
                source['publication_mode']='draft' if module.municipal_source(source) else 'auto'
                if source.get('authorized') is True:counts[source['publication_mode']]+=1
            configbytes=(json.dumps(data,ensure_ascii=False,indent=2)+'\n').encode()
            stagedconfig=stage/'feeds.json';stagedconfig.write_bytes(configbytes);module.load_sources(stagedconfig)
            if worker.read_bytes()!=before or config.read_bytes()!=beforeconfig:raise RuntimeError('Arquivos alterados durante a validação; nada sobrescrito.')
            backup=Path('/var/backups/giro73-rss')/('policy-20261028-'+time.strftime('%Y%m%dT%H%M%SZ',time.gmtime()))
            backup.mkdir(parents=True,exist_ok=False);os.chmod(backup,0o700)
            (backup/'coletor_rss.py').write_bytes(before);(backup/'feeds.json').write_bytes(beforeconfig)
            ws=worker.stat();cs=config.stat()
            changed=True
            atomic_write(worker,code,ws.st_uid,ws.st_gid,ws.st_mode&0o777)
            atomic_write(config,configbytes,cs.st_uid,cs.st_gid,cs.st_mode&0o777)
            final=subprocess.run([str(python),str(worker),'--policy-self-test'],capture_output=True,text=True,timeout=30)
            if final.returncode:raise RuntimeError('Autoteste do arquivo instalado falhou.')
            print('RSS_POLICY_INSTALLED')
            print('SHA256:',hashlib.sha256(worker.read_bytes()).hexdigest())
            print('Fontes autorizadas:',json.dumps(counts))
            print('Backup:',backup)
            print('Cron preservado. Nenhuma notícia foi criada; nenhuma chamada de IA foi feita. Próximo cron usará a nova versão.')
        except Exception:
            if changed:
                atomic_write(worker,before,ws.st_uid,ws.st_gid,ws.st_mode&0o777)
                atomic_write(config,beforeconfig,cs.st_uid,cs.st_gid,cs.st_mode&0o777)
                print('Rollback concluído: arquivos anteriores restaurados; travas do WordPress permanecem ativas.')
            raise
        finally:
            shutil.rmtree(stage,ignore_errors=True)


if __name__=='__main__':
    try:
        import urllib.request,gzip
        url='https://raw.githubusercontent.com/dicasdoflaviog/Giro73/1bdb5e1ff3de50a71374c4f5008e3418ffeaf298/portal-noticias-mcp/automacao-rss/coletor_rss.py'
        code=urllib.request.urlopen(url,timeout=40).read()
        bundle={'worker_sha256':'3ad657a32f055ce3e3ba9516f81e5a14e6ebfb3a70df80fa4261ec6ab55380f1',
                'worker_gzip_base64':base64.b64encode(gzip.compress(code,mtime=0)).decode()}
        apply(bundle)
    except Exception as error:
        print('INSTALAÇÃO INTERROMPIDA:',str(error))
        raise SystemExit(2)
