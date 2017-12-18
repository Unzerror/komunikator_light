#!/bin/bash
repo_name=komunikator_light

cd ~

release=$(lsb_release -cs)
arch=$(uname -m)

echo "Komunikator 1.5a0 ($release $arch)"
apt -qq update

echo "Installer: Generating and setting the DB user passwords..."
	apt install -y pwgen
	dbuserpw=$(pwgen -cAns -1)
	printf "ПОЖАЛУЙСТА, КАК СЛЕДУЕТ ЗАПОМНИТЕ ПАРОЛЬ И ПОТОМ УДАЛИТЕ ЭТОТ ФАЙЛ\nПароль пользователя root для доступа к базе данных MySQL\n$dbuserpw" > ~/DB_root_password.txt
	echo "mysql-server mysql-server/root_password password $dbuserpw" | debconf-set-selections
	echo "mysql-server mysql-server/root_password_again password $dbuserpw" | debconf-set-selections
	apt install -y mysql-server

echo "Installer: Installing some tools and dependencies..."
	apt install -y libmysqlclient20 libgcc1 libmysqlclient20 libstdc++6 libc6 libcap2-bin ssh adduser libyate5.2.0 yate-core madplay lame ntp nginx php-fpm php-cli php-db php-mysql

echo "Installer: Installing the distro packages..."
if [ "$arch" = 'x86_64' ]
then
	wget http://mirrors.kernel.org/ubuntu/pool/universe/y/yate/yate_5.4.0-1-1ubuntu2_amd64.deb
	wget http://mirrors.kernel.org/ubuntu/pool/universe/y/yate/yate-scripts_5.4.0-1-1ubuntu2_amd64.deb
	wget http://mirrors.kernel.org/ubuntu/pool/universe/y/yate/yate-mysql_5.4.0-1-1ubuntu2_amd64.deb
	dpkg -i yate*_amd64.deb
	rm -f yate*_amd64.deb
else
	wget http://mirrors.kernel.org/ubuntu/pool/universe/y/yate/yate_5.4.0-1-1ubuntu2_i386.deb
    wget http://mirrors.kernel.org/ubuntu/pool/universe/y/yate/yate-scripts_5.4.0-1-1ubuntu2_i386.deb
	wget http://mirrors.kernel.org/ubuntu/pool/universe/y/yate/yate-mysql_5.4.0-1-1ubuntu2_i386.deb
	dpkg -i yate*_i386.deb
	rm -f yate*_i386.deb
fi

echo "Installer: Installing the package dependencies..."
	apt install -f -y

echo "Installer: clone source Kommunikator..."
	git clone --depth=1 https://github.com/komunikator/$repo_name.git

echo "Installer: Configuring the database..."
	mysql -uroot -p$dbuserpw -e "CREATE USER 'kommunikator'@'localhost' IDENTIFIED BY 'kommunikator';"
    mysql -uroot -p$dbuserpw -e "create database kommunikator"
    mysql -uroot -p$dbuserpw kommunikator < ~/$repo_name/SQL/shema_mysql.sql
    mysql -uroot -p$dbuserpw -e "GRANT ALL PRIVILEGES ON * . * TO 'kommunikator'@'localhost';"
	mysql -uroot -p$dbuserpw -e "FLUSH PRIVILEGES;"

echo "Installer: Configuring server..."
	pear install DB
	sed -i "s/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/" /etc/php/7.0/fpm/php.ini
	sed -i "/types_hash_max_size/ a\\t\tclient_max_body_size 50m;" /etc/nginx/nginx.conf
	
	sed -i "s/root \/var\/www\/html;/root \/var\/www;/" /etc/nginx/sites-available/default
	sed -i "s/index index.html index.htm index.nginx-debian.html;/index index.php index.html index.htm;/" /etc/nginx/sites-available/default
	sed -i "s/server_name _;/server_name kommunikator;/" /etc/nginx/sites-available/default
	sed -i "/pass the PHP scripts to FastCGI server listening/ a\\tlocation ~ \\.php$ {\\ninclude snippets\/fastcgi-php.conf;" /etc/nginx/nginx.conf















rm -Rf $repo_name