# annotation-editors — production sign-language annotation editors

Self-contained extracts of the two **production** annotation editors launched from
`zinnen.html`, each in its own runnable subfolder. Companion to `zin-lite` (the sentence
browser). Extracted from `/web/zin` without disturbing the originals.

| folder       | tool                          | launched from `zinnen.html` by |
|--------------|-------------------------------|--------------------------------|
| `subBeta8/`  | AI-assisted subtitle editor   | `editEAF-AI` button (the live `signcollect.nl/zin/subBeta8.html`) |
| `3DAnn2/`    | 3D motion-capture annotator   | `editMocap` button (line 1461) |

Each subfolder is its own web docroot with a 2-level layout mirroring the original, so no
include/asset paths had to be edited. Serve a subfolder's root as docroot and open
`/zin/<tool>.html`.

## subBeta8/  (subtitle editor)

```
subBeta8/                      <- docroot
├── mysql_config.php           -> symlink to /web/mysql_config.php (shared creds)
├── userProtect.js  login.html  login_sc.php  users_api.php     # auth guard + chain
└── zin/
    ├── subBeta8.html          # the editor
    ├── getZinnen.php          # main backend (6 calls)  -> ../mysql_config.php
    ├── getGlossVideo.php      # gloss video lookup (2 calls)
    ├── getHandshapes.php      # handshapes (1 call)     -> HandshapeClient.php
    ├── HandshapeClient.php    # WebSocket client (ws://localhost:9000 at runtime)
    ├── syncEafToDatabase.php  # required by getZinnen.php -> api/mysql_config.php
    ├── SignSegmentationClient.php   # required by getZinnen.php
    ├── api/mysql_config.php
    └── eaf/zin/  cache/       # (empty) runtime dirs
```

**Remote services (stay remote, not bundled):** `signcollect.nl/sign-segmenter`,
`/sign-spotter`, `/ISS_Server/ws` (websocket), and media under
`signcollect.nl/gebarenoverleg_media/studioFilesMini/`. subBeta8 has **no** large local assets.

## 3DAnn2/  (3D mocap annotator)

```
3DAnn2/                        <- docroot
├── mysql_config.php           -> symlink to /web/mysql_config.php
├── animMIDI/babyloncc/dist/
│   ├── Backdrop.glb           # backdrop mesh   (/animMIDI/babyloncc/dist/Backdrop.glb)
│   └── environment.envbin     # IBL environment (.env served as .envbin; Apache blocks *.env)
└── zin/
    ├── 3DAnn2.html            # the editor (Babylon.js from CDN)
    ├── PalmerPolo1024uastc.glb # 34MB base avatar  (/zin/PalmerPolo1024uastc.glb)  ** see note **
    ├── getZinnen.php          # main backend (6 calls)
    ├── getRazerVideo.php      # razer .mkv lookup (2 calls; globs /web/gebarenoverleg_media/razerFiles/)
    ├── syncEafToDatabase.php  SignSegmentationClient.php   # required by getZinnen.php
    ├── api/mysql_config.php
    ├── record3D/out/          # (empty) runtime render output: record3D/out/<base>.mp4
    └── eaf/zin/  cache/       # (empty) runtime dirs
```

3DAnn2 has **no auth guard** (no `userProtect.js`) — matching the original. Mocap animation
GLBs are loaded dynamically at runtime from server paths (e.g.
`/gebarenoverleg_media/fbx/post_processed/…`), so they are not bundled.

## Not included (deliberately)

- **Other versions**: `subBeta`–`subBeta7`, `3DAnn`/`3DAnn1`, and all `*.backup*` — only the
  production versions above are extracted.
- **Runtime data**: existing EAF/SRT files, recorded mp4s, mocap GLBs, videos — the empty
  `eaf/zin/`, `cache/`, `record3D/out/` dirs are placeholders; live data comes from the DB
  and the `signcollect.nl` media server.

## ⚠️ The 34 MB avatar model & git

`3DAnn2/zin/PalmerPolo1024uastc.glb` (34 MB) is **present on disk** (the folder is fully
runnable) but is **git-ignored** to keep the repo lean — a 34 MB binary bloats git history.
To version it too, remove its line from `.gitignore` (or set up git-lfs) and re-add.

## Running

1. Point a PHP web server's docroot at `subBeta8/` or `3DAnn2/` (e.g. `php -S 0.0.0.0:8000`
   from inside it, or Apache/nginx).
2. Ensure the MySQL DB in `/web/mysql_config.php` (`admin_gebarenoverleg@localhost`) is reachable.
3. Open `/zin/subBeta8.html` or `/zin/3DAnn2.html`.

> `mysql_config.php` is a symlink to the shared `/web/mysql_config.php` (real credentials, not
> duplicated). Keep this project private.
