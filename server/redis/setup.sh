sudo mkdir /var/redis
sudo mkdir /etc/redis
sudo cp 6379.conf /etc/redis/
sudo cp redis_6379 /etc/init.d/
sudo chkconfig --add redis_6379
sudo chkconfig redis_6379 on
echo "Make sure to edit sysctl."
