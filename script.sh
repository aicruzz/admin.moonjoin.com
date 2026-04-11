#!/bin/bash
(crontab -l | grep -v " /home/moonxmmg/admin.moonjoin.com/artisan dm:disbursement") | crontab -
(crontab -l ; echo "58 08 * * 1  /home/moonxmmg/admin.moonjoin.com/artisan dm:disbursement") | crontab -
(crontab -l | grep -v "/system/orders/disbursement.php /home/moonxmmg/admin.moonjoin.com/artisan store:disbursement") | crontab -
(crontab -l ; echo "08 01 * * 6 /system/orders/disbursement.php /home/moonxmmg/admin.moonjoin.com/artisan store:disbursement") | crontab -
