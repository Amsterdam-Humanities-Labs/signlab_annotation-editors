# signlab_annotation-editors
The two sign-language annotation editors that `zinnen.html` opens. This repo is the only copy; [signlab_zinnen-annotation](https://github.com/Amsterdam-Humanities-Labs/signlab_zinnen-annotation) and [signlab_patient-info](https://github.com/Amsterdam-Humanities-Labs/signlab_patient-info) link here.

## What it does
| Folder | Tool | Opened from |
|---|---|---|
| `subBeta8/` | subtitle editor with AI suggestions | `editEAF` / `editEAF-AI` in `zinnen.html`, and `overview_hh.html` |
| `3DAnn3/` | motion-capture annotator in 3D | `editMocap` in `zinnen.html` |

Both are static HTML/JS. They send every server call to [signlab_zinnen-annotation](https://github.com/Amsterdam-Humanities-Labs/signlab_zinnen-annotation) on the same origin (`/zin/getZinnen.php` and others). EAF files therefore land in `/web/zin/eaf/zin/`.

## Where it runs
Core server, `<root>/annotation-editors` (`/web` on production). URLs:
`/annotation-editors/subBeta8/zin/subBeta8.html?filename=…` and `/annotation-editors/3DAnn3/zin/3DAnn3.html?glb=…`

## Status
Production.

## How to run / deploy
There is no build step. The stack deploys it through `interface_deploy/scripts/repos.tsv` in
[signlab_signcollect-stack](https://github.com/Amsterdam-Humanities-Labs/signlab_signcollect-stack).

## Configuration
None. The repo has no PHP and no credentials.

## Dependencies
| Path | Comes from |
|---|---|
| `/zin/getZinnen.php`, `getGlossVideo.php`, `getHandshapes.php`, `getRazerVideo.php`, `/zin/record3D/out/` | signlab_zinnen-annotation |
| `/animMIDI/babyloncc/dist/` (avatar, `environment.envbin`) | [signlab_mocap-postprocessing](https://github.com/Amsterdam-Humanities-Labs/signlab_mocap-postprocessing) |
| `/signbank_data/glosses_transformed.json` (falls back to `/glosses_transformed.json`) | [signlab_signCollect-v2](https://github.com/Amsterdam-Humanities-Labs/signlab_signCollect-v2) |
| `/userProtect.js` (subBeta8 and 3DAnn3) | the stack's `interface_deploy/web_extra/` |

Other services, hardcoded to `https://signcollect.nl` (localhost when opened locally): `/sign-segmenter`, `/sign-spotter`, `/ISS_Server/ws`, and media under `/gebarenoverleg_media/studioFilesMini/`. subBeta8 also loads `/uploads/<gloss>.mp4`.
