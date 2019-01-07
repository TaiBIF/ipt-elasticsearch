<?php
// 需要先設定好 github 免密碼認證
exec("cd /path/to/data && /path/to/bin/git add . && /path/to/bin/git commit -m \"".date("Y-m-d\TH:i:s")."\" && /path/to/bin/git push");
