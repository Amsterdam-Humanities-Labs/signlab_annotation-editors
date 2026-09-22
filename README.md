# annotation-editors
The two sign-language annotation editors opened from `zinnen.html` (source of truth; zin and hh link here).

## What it does
| folder | tool | launched from |
|---|---|---|
| `subBeta8/` | AI-assisted subtitle editor | `editEAF` / `editEAF-AI` in `zinnen.html`, `overview_hh.html` |
| `3DAnn3/` | 3D motion-capture annotator | `editMocap` in `zinnen.html` |

Static HTML/JS only. All server calls go to `signlab_zin` on the same origin (`/zin/getZinnen.php` etc.), so EAFs land in `/web/zin/eaf/zin/`.

## Where it runs
Core server, `<root>/annotation-editors` (`/web` on production). URLs:
`/annotation-editors/subBeta8/zin/subBeta8.html?filename=…`, `/annotation-editors/3DAnn3/zin/3DAnn3.html?glb=…`

## Status
Production.

## How to run / deploy
No build step. Deployed by `interface_deploy/scripts/repos.tsv` in
[signlab_signcollect-stack](https://github.com/Amsterdam-Humanities-Labs/signlab_signcollect-stack).

## Configuration
None. No PHP and no credentials in this repo.

## Dependencies
| path | provided by |
|---|---|
| `/zin/getZinnen.php`, `getGlossVideo.php`, `getHandshapes.php`, `getRazerVideo.php`, `/zin/record3D/out/` | `signlab_zin` |
| `/animMIDI/babyloncc/dist/` (avatar, `environment.envbin`) | `signlab_sC-Animation-PP` |
| `/signbank_data/glosses_transformed.json` | `signlab_signCollect-v2` |
| `/userProtect.js` (subBeta8 only; 3DAnn3 has no auth guard) | stack `interface_deploy/web_extra/` |

Remote services: `/sign-segmenter`, `/sign-spotter`, `/ISS_Server/ws`, media under `/gebarenoverleg_media/studioFilesMini/`.
