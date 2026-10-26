#!/usr/bin/env python3
"""Aplica a seleção de fontes automáticas aprovada pelo responsável do Giro73.

Sem --apply, apenas mostra a proposta. Não consulta APIs nem cria matérias.
"""
import argparse
from datetime import datetime, timezone
import json
import os
from pathlib import Path
import shutil
import tempfile
from urllib.parse import urlsplit


def identity(url):
    if not isinstance(url,str):
        raise ValueError("URL de feed inválida.")
    p = urlsplit(url)
    return (p.scheme.lower(),(p.hostname or "").lower(),p.port,p.path.rstrip("/"),p.query)


APPROVED = {
    identity(url) for url in (
        "https://agenciabrasil.ebc.com.br/rss/ultimasnoticias/feed.xml",
        "https://agenciabrasil.ebc.com.br/rss/economia/feed.xml",
        "https://g1.globo.com/rss/g1/bahia/",
        "https://portalleodias.com/feed",
    )
}


def configure(path, apply=False):
    path = Path(path)
    original = path.read_bytes()
    data = json.loads(original.decode("utf-8-sig"))
    feeds = data.get("feeds") if isinstance(data,dict) else None
    if not isinstance(feeds,list) or not feeds or any(not isinstance(f,dict) for f in feeds):
        raise ValueError('Esperado um objeto JSON com lista "feeds" não vazia.')
    automatic = 0
    for feed in feeds:
        mode = "auto" if identity(feed.get("url","")) in APPROVED else "draft"
        feed["publication_mode"] = mode
        if mode == "auto":
            feed["auto_min_chars"] = 600
            feed["auto_max_age_hours"] = 24
            automatic += feed.get("authorized") is True
        print(f'{feed.get("name",feed.get("id","fonte"))}: {mode}'
              + (" (fonte desabilitada)" if feed.get("authorized") is not True else ""))
    if not automatic:
        raise ValueError("Nenhuma das fontes automáticas aprovadas está cadastrada e habilitada; configuração não alterada.")
    if not apply:
        print("Simulação: nenhum arquivo alterado. Use --apply para salvar.")
        return
    if path.read_bytes() != original:
        raise ValueError("Configuração alterada durante a leitura; tente novamente.")
    previous = path.stat()
    fd, temporary = tempfile.mkstemp(prefix=".g73-publicacao-",dir=path.parent)
    try:
        with os.fdopen(fd,"w",encoding="utf-8") as output:
            json.dump(data,output,ensure_ascii=False,indent=2)
            output.write("\n"); output.flush(); os.fsync(output.fileno())
        os.chown(temporary,previous.st_uid,previous.st_gid)
        os.chmod(temporary,0o640)
        backup = str(path)+".bak-publicacao-"+datetime.now(timezone.utc).strftime("%Y%m%dT%H%M%S%fZ")
        shutil.copy2(path,backup)
        if path.read_bytes() != original:
            raise ValueError("Configuração alterada durante a gravação; tente novamente.")
        os.replace(temporary,path)
        print(f"Configuração salva; {automatic} feeds habilitados para modo automático. Backup: {backup}")
    finally:
        if os.path.exists(temporary):
            os.unlink(temporary)


def main(argv=None):
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--config",type=Path,default=Path("/etc/giro73-rss.json"))
    parser.add_argument("--apply",action="store_true")
    args = parser.parse_args(argv)
    try:
        configure(args.config,args.apply)
    except (OSError,ValueError,TypeError):
        print("Falha na configuração: confira o JSON, os feeds cadastrados e as permissões. Nenhuma chave foi exibida.")
        return 2
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
