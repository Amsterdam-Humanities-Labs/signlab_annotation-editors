# annotation-editors — production sign-language annotation editors

**Source of truth** for the two annotation editors launched from `zinnen.html`.
They used to live in `signlab_zin` (and were forked again into `signlab_hh`);
those copies drifted apart, so the editors now live here only and the other
repos link to this deployment.

| folder      | tool                        | launched from                          |
|-------------|-----------------------------|----------------------------------------|
| `subBeta8/` | AI-assisted subtitle editor | `editEAF` / `editEAF-AI` in `zinnen.html`, and `overview_hh.html` |
| `3DAnn3/`   | 3D motion-capture annotator | `editMocap` in `zinnen.html`           |

Each subfolder is its own web docroot with a 2-level layout mirroring the
original `/web` tree, so no include or asset paths had to be edited. Serve a
subfolder's root as docroot and open `/zin/<tool>.html`.

## Retired

- **`3DAnn2`** — superseded by `3DAnn3`, which reads the FBXtoGLBCompression
  output. The two were never interchangeable: 3DAnn2 hardcoded `POS_SCALE=100`
  for legacy metre-based GLBs. Removed 2026-09-07; recoverable from git history.
- **`subBeta` … `subBeta7`** — superseded iterations, removed from `signlab_zin`.

## Runtime dependencies

Neither editor bundles large assets. Both need these to be served by other
deployments on the same origin:

| path | provided by |
|---|---|
| `/animMIDI/babyloncc/dist/environment.envbin` | `signlab_sC-Animation-PP` at `/web/animMIDI` |
| `/animMIDI/babyloncc/dist/PalmerPolo1024uastc.glb` | same — 34 MB base avatar |
| `/signbank_data/glosses_transformed.json` | rebuilt by the Signbank connector in menu_beta; `/glosses_transformed.json` is the pre-connector path and is still read as a fallback |
| `/userProtect.js` | the deploy's shared auth guard (subBeta8 only) |
| `mysql_config.php` | one level above `zin/`; symlink to `/web/mysql_config.php` |

3DAnn3 previously loaded the avatar from `/zin/PalmerPolo1024uastc.glb`, a
byte-identical 34 MB copy of the `animMIDI` one. It now uses the `animMIDI`
copy so the file exists once.

Remote services stay remote and are not bundled: `/sign-segmenter`,
`/sign-spotter`, `/ISS_Server/ws`, and media under
`/gebarenoverleg_media/studioFilesMini/`.

3DAnn3 has no auth guard (no `userProtect.js`), matching the original.

## Deployment

Deployed by `interface_deploy/scripts/install.sh` in `signlab_signcollect-stack`.
