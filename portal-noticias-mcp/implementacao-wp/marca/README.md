# Marcas oficiais Giro73

`LOGO REDONDA GIRO73.png` é a versão original disponibilizada pela redação na Biblioteca de Mídia do WordPress, preservada sem redesenho. Dimensões: 164 × 164, PNG com transparência. Mídia WordPress 56, aplicada como favicon e logo da organização no Rank Math.

As marcas restantes foram recuperadas dos anexos enviados pela redação ao WordPress e normalizadas com `otimizar-marca.py`. Os PNGs/WebPs desta pasta preservam exatamente os pixels RGBA dos originais disponíveis, sem ampliação ou redesenho. O manifesto registra dimensões, tamanhos e hashes.

| Arquivo WebP transparente | Dimensões | Bytes | Mídia WordPress | Aplicação |
|---|---|---|---|---|
| `logo-giro73.webp` | 221 × 59 | 2.688 | 63 | Header amarelo e `site_logo` |
| `logo-branca.webp` | 221 × 59 | 1.306 | 64 | Footer roxo |
| `selo.webp` | 125 × 58 | 1.964 | 65 | Selo oficial disponível na Mídia |

As versões PNG equivalentes usam os mesmos nomes simples. O padrão vigente exige fotografias reais nas capas, sem selo, logo ou título gravado. As marcas oficiais permanecem no header, footer, favicon e Schema. O procedimento de seleção de fotos e créditos está em [README-capas.md](../README-capas.md).

Para repetir a normalização com originais novos:

```bash
python otimizar-marca.py --logo /caminho/logo-original.png --white /caminho/logo-branca-original.png --seal /caminho/selo-original.png --output-dir /tmp/giro73-marca
```

O otimizador só produz arquivos locais. Nesta implantação, os WebPs foram enviados com `wp_upload_media` do Easy MCP, e os anexos foram registrados pelo WordPress. O terminal da nuvem não possui montagem remota do site nem conexão SQL/SSH/SFTP com a hospedagem.
