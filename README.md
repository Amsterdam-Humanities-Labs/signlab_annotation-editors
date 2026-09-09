# annotation-editors — production sign-language annotation editors

**Source of truth** for the two annotation editors launched from `zinnen.html`.

## What it does

Holds the two editors an annotator actually opens from the Zinnen interface,
plus the small PHP endpoints each needs to read a sentence and write its
annotation back.

| folder      | tool                        | launched from                          |
|-------------|-----------------------------|----------------------------------------|
| `subBeta8/` | AI-assisted subtitle editor | `editEAF` / `editEAF-AI` in `zinnen.html`, and `overview_hh.html` |
| `3DAnn3/`   | 3D motion-capture annotator | `editMocap` in `zinnen.html`           |

Both load a sentence and its video, let the annotator lay glosses out on a
timeline, and write the result back through `syncEafToDatabase.php` and into
`zin/eaf/zin/`. `subBeta8` additionally calls out to the handshape and
sign-segmentation services; `3DAnn3` reads the FBXtoGLBCompression output and
renders it against the Babylon avatar from `animMIDI`.

They used to live in `signlab_zin` (and were forked again into `signlab_hh`);
those copies drifted apart, so the editors live here only and the other repos
link to this deployment.

Each subfolder is shaped like its own docroot — a 2-level layout mirroring the
original `/web` tree, with `zin/` beneath it — so no include or asset paths had
to be edited when the editors moved out of `signlab_zin`.

## Where it runs

The **signcollect core server** (production VPS), from
`<root>/annotation-editors`. The demo hosts deploy the same tree: dev2 under
`/web`, dev-1 under `/srv/signcollect/web`.

There is no vhost or alias per editor. The whole repo is served as one
directory and `zinnen.html` links into it by full path:

    /annotation-editors/subBeta8/zin/subBeta8.html?filename=…
    /annotation-editors/3DAnn3/zin/3DAnn3.html?glb=…

The per-editor `zin/` level is what makes each editor's `../mysql_config.php`
and `zin/api/mysql_config.php` resolve — see *Configuration*.

## Status

**Production.** These are the editors annotators use daily.

## How to deploy it

No build step — PHP, static HTML and JavaScript, served directly.

Deployment is by `interface_deploy/scripts/repos.tsv` in
`signlab_signcollect-stack`, which maps `annotation-editors` → this repo on
branch `main`. `scripts/install.sh` runs `host-bootstrap.sh`, which clones (or
fetches and hard-resets) the repo into `<root>/annotation-editors`. On a demo
host `rewrite-urls.sh` then repoints hardcoded `signcollect.nl` URLs at the
demo's hostname.

## Configuration

Nothing secret is in git. Both editors reach the database the way the rest of
the estate does:

| file | what it is |
|---|---|
| `<editor>/mysql_config.php` and `<editor>/zin/api/mysql_config.php` | database credentials, at the two depths the PHP includes reach for them. `host-config.sh` symlinks both to `<root>/mysql_config.php`, so there is still one credential file on the host. Gitignored. |
| `<editor>/zin/cache/`, `<editor>/zin/eaf/zin/` | runtime output directories, created and chmod'ed 775 by `host-config.sh`. Only `.gitkeep` is in git. |
| `sc_paths.php` | vendored copy of `signcollect-lib`'s install-root resolver. In git, but **do not edit it here** — edit the library's `consumer/sc_paths.php` and re-copy; the deploy checksums the copies against each other. |

`<root>` is the install root: `/web` on production and dev2,
`/srv/signcollect/web` on dev-1.

**Known gap:** `3DAnn3/zin/getZinnen.php` does
`require_once __DIR__ . '/mocapFiles.php'`, and `mocapFiles.php` is not in this
repository and is not gitignored. Either production has a copy that was never
committed, or that code path is dead. *TODO: confirm on the production host
whether `3DAnn3/zin/mocapFiles.php` exists, and commit it if it does.*

## Dependencies

Neither editor bundles large assets. Both need these to be served by other
deployments on the same origin:

| path | provided by |
|---|---|
| `/animMIDI/babyloncc/dist/environment.envbin` | `signlab_sC-Animation-PP` at `<root>/animMIDI` |
| `/animMIDI/babyloncc/dist/PalmerPolo1024uastc.glb` | same — 34 MB base avatar |
| `/signbank_data/glosses_transformed.json` | rebuilt by the Signbank connector in `signlab_signCollect-v2`; `/glosses_transformed.json` is the pre-connector path and is still read as a fallback |
| `/userProtect.js` | the deploy's shared auth guard, vendored in `interface_deploy/web_extra/` (subBeta8 only) |
| MySQL `admin_gebarenoverleg` | `matched_transcriptions`, `sentences`, `sentences_logs` and `users` — read and written by `getZinnen.php` and `syncEafToDatabase.php` |
| `signlab_zin` | not a code dependency, but the launcher: `zinnen.html` is what opens these editors |
| `signlab_signcollect-lib` | optional, at `<root>/lib` — `sc_paths.php` falls back to `/web` without it |

3DAnn3 previously loaded the avatar from `/zin/PalmerPolo1024uastc.glb`, a
byte-identical 34 MB copy of the `animMIDI` one. It now uses the `animMIDI`
copy so the file exists once.

Remote services stay remote and are not bundled: `/sign-segmenter`,
`/sign-spotter`, `/ISS_Server/ws`, and media under
`/gebarenoverleg_media/studioFilesMini/`.

3DAnn3 has no auth guard (no `userProtect.js`), matching the original.

## Retired

- **`3DAnn2`** — superseded by `3DAnn3`, which reads the FBXtoGLBCompression
  output. The two were never interchangeable: 3DAnn2 hardcoded `POS_SCALE=100`
  for legacy metre-based GLBs. Removed 2026-09-07; recoverable from git history.
- **`subBeta` … `subBeta7`** — superseded iterations, removed from `signlab_zin`.
