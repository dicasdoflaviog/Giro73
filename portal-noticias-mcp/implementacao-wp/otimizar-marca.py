#!/usr/bin/env python3
"""Normaliza marcas Giro73 em PNG/WebP transparentes; nenhum acesso remoto."""
import argparse
import hashlib
import json
from pathlib import Path
from PIL import Image


def optimize(source, output, name):
    with Image.open(source) as original:
        if original.format != "PNG":
            raise ValueError(f"{source.name}: esperado PNG original.")
        image = Image.new("RGBA", original.size)
        image.paste(original.convert("RGBA"))
    # Não ampliar fontes pequenas; descartar metadados e preservar a transparência.
    image.thumbnail((512, 512), Image.Resampling.LANCZOS)
    result = {"width": image.width, "height": image.height, "files": {}}
    for extension in ("png", "webp"):
        path = output / f"{name}.{extension}"
        if extension == "png":
            image.save(path, "PNG", optimize=True, compress_level=9)
        else:
            image.save(path, "WEBP", lossless=True, exact=True, method=6)
        with Image.open(path) as check:
            if check.size != image.size or check.convert("RGBA").tobytes() != image.tobytes():
                raise ValueError(f"{path.name}: pixels/transparência não foram preservados.")
        content = path.read_bytes()
        result["files"][extension] = {"filename": path.name, "bytes": len(content),
                                      "sha256": hashlib.sha256(content).hexdigest()}
    return result


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    for key in ("logo", "white", "seal"):
        parser.add_argument(f"--{key}", type=Path, required=True)
    parser.add_argument("--output-dir", type=Path, required=True)
    args = parser.parse_args()
    args.output_dir.mkdir(parents=True, exist_ok=True)
    mapping = ((args.logo, "logo-giro73"), (args.white, "logo-branca"), (args.seal, "selo"))
    manifest = {name: optimize(source, args.output_dir, name) for source, name in mapping}
    (args.output_dir / "manifesto-marca.json").write_text(json.dumps(manifest, indent=2) + "\n")
    print(json.dumps(manifest, indent=2))


if __name__ == "__main__":
    main()
