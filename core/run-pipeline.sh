#!/bin/bash

php entropy.php
sleep 2 #beri jeda 2 detik utk menghindari collison in case entropy.php belum selesai
php dynamic-k.php
sleep 2
php threshold.php
sleep 2
php classification.php