#!/bin/sh

while :
do
  gearman -f ebid_ex_con_suc_search '{"recently":"true"}'
  sleep 30
done

