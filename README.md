# Playground Theme

OpenCart 4 theme for the opencart-playground stand. This repo sits **on the same level** as the playground folder (sibling of `opencart-playground/`), so you can version it in its own git repo.

## Structure

- **`src_oc4/`** — extension contents, mounted into the container as `extension/playground_theme/`
  - `install.json` — theme metadata (type: theme, code: playground_theme)
  - `catalog/controller/startup/playground_theme.php` — registers template overrides and `controller/product/thumb/before` to inject thumb_40, thumb_136, thumb_250 for picture/srcset
  - `catalog/view/template/` — Twig overrides (e.g. `common/header.twig`, `common/pagination.twig`, `product/product.twig`, `product/thumb.twig`); `product/thumb.twig` uses `<picture>` with `srcset` (40w, 136w, 250w) when multiple sizes are available (injected by `controller/product/thumb/before`). Add files here to override more templates.
  - `catalog/view/stylesheet/custom.css` — minimal theme CSS (header loads this instead of the default OC stylesheet; Bootstrap + icons only otherwise)
  - `admin/` — theme settings in Admin (controller, language, view)

## Usage in playground

1. Place this repo next to **opencart-playground** (e.g. `opencart-playground/` and `opencart-theme/` as siblings). The playground’s `compose.yml` mounts `../opencart-theme/src_oc4` into the container.
2. In Admin: **Extensions → Extensions** → Type: **Theme** → **Playground Theme** → Install → Edit → enable Status → Save.
3. **System → Settings** → Edit store → **Store** tab → **Theme** = **Playground Theme** → Save.
4. **Developer Settings** → refresh Theme + Cache.

## Adding overrides

Copy any template from the playground’s `ocdata/catalog/view/template/` into this repo’s `src_oc4/catalog/view/template/` keeping the same path (e.g. `product/product.twig`). When the theme is active and the template exists here, it will be used instead of the default.

**Currency and language:** The header nav outputs `{{ currency }}` and `{{ language }}`, which are rendered by `common/currency.twig` and `common/language.twig`. This theme overrides both under `common/` so you can edit their markup and styling (e.g. to match the top nav).

## Git repo

This folder is intended as its own repo. From the parent directory (same level as `opencart-playground`):

```bash
cd opencart-theme
git init
git add .
git commit -m "Initial Playground Theme"
# add remote and push
```

To add as a submodule from the parent (e.g. `extentions-stand`):

```bash
git submodule add <your-theme-repo-url> opencart-theme
```

Keep `opencart-theme` next to `opencart-playground` so the compose volume `../opencart-theme/src_oc4` works.
