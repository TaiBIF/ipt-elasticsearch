# ipt-elasticsearch
[prototype] Dataset metadata search for hosted IPT resources

## Statistics
- See https://github.com/trashmai/ipt-statistics-data for auto-generated statistics.

## Visualization
- `data=ipt_statistics_${YYYYMM}-$YYYYMM}.json`
- http://test.taibon.tw/indexIPT/chart.html?data=ipt_statistics_201805-201901.json

## Crontab
```
0 3 * * * /path/to/bin/php /path/to/es_put_eml.php -f
0 5 * * 6 /path/to/bin/php /path/to/es_put_eml.php && /path/to/bin/php /path/to/gen_data_for_viz.php && /path/to/bin/php /path/to/git_commit_data.php
```
