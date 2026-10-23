#!/usr/bin/env python3
"""Placeholders de interface: monogramas WebP. Otimiza a ilustração opcional sem adicionar texto à imagem."""
from pathlib import Path
import argparse,json
from PIL import Image,ImageDraw,ImageFont,ImageOps
PERSONAS=[('arthur-valente','AV','#3A86FF'),('helena-gusmao','HG','#FF006E'),('lucas-vianna','LV','#2C1067'),('beto-santana','BS','#D97706'),('malu-santoro','MS','#FB5607'),('clara-mendes','CM','#15803D')]
def build(output,illustration=None):
 output.mkdir(parents=True,exist_ok=True);font=ImageFont.truetype('/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',82);manifest=[]
 for slug,initials,color in PERSONAS:
  rgb=tuple(bytes.fromhex(color[1:]));bg=tuple(round(245*.92+c*.08) for c in rgb)
  image=Image.new('RGB',(256,256),bg);draw=ImageDraw.Draw(image);draw.ellipse((12,12,244,244),outline=rgb,width=5);box=draw.textbbox((0,0),initials,font=font);draw.text(((256-(box[2]-box[0]))/2,(256-(box[3]-box[1]))/2-box[1]),initials,font=font,fill=rgb)
  path=output/f'avatar-{slug}.webp';image.resize((64,64),Image.Resampling.LANCZOS).save(path,'WEBP',quality=88,method=6);manifest.append({'slug':slug,'file':path.name,'width':64,'height':64,'bytes':path.stat().st_size})
 if illustration:
  path=output/'agenda73-ilustracao.webp';image=ImageOps.fit(Image.open(illustration).convert('RGB'),(600,375),method=Image.Resampling.LANCZOS)
  for quality in (78,72,65,58):
   image.save(path,'WEBP',quality=quality,method=6)
   if path.stat().st_size<60000:break
  manifest.append({'file':path.name,'width':600,'height':375,'bytes':path.stat().st_size,'credit':'Ilustração gerada por IA — demonstração, não retrata um evento confirmado.'})
 (output/'manifesto.json').write_text(json.dumps(manifest,ensure_ascii=False,indent=2));print(json.dumps(manifest))
if __name__=='__main__':
 parser=argparse.ArgumentParser();parser.add_argument('--output',type=Path,default=Path(__file__).parent/'autoridade-assets');parser.add_argument('--illustration',type=Path);args=parser.parse_args();build(args.output,args.illustration)
