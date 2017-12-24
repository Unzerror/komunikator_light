#!/bin/bash
repo_name=komunikator_light

cd ~

release=$(lsb_release -cs)
arch=$(uname -m)

echo "Komunikator 1.5a0 ($release $arch)"
udistro="xenial"
if [ $udistro != $release ]
then
echo "Установка Komunikator 1.5.a0 может производиться только на ОС Ubuntu 16.04"
exit 1
fi


apt -qq update

echo "Installer: Generating and setting the DB user passwords..."
	apt install -y pwgen
	dbuserpw=$(pwgen -cAns -1)
	printf "ПОЖАЛУЙСТА, КАК СЛЕДУЕТ ЗАПОМНИТЕ ПАРОЛЬ И ПОТОМ УДАЛИТЕ ЭТОТ ФАЙЛ\nПароль пользователя root для доступа к базе данных MySQL\n$dbuserpw" > ~/DB_root_password.txt
	echo "mysql-server mysql-server/root_password password $dbuserpw" | debconf-set-selections
	echo "mysql-server mysql-server/root_password_again password $dbuserpw" | debconf-set-selections
	apt install -y mysql-server

echo "Installer: Installing some tools and dependencies..."
	apt install -y libmysqlclient20 libgcc1 libmysqlclient20 libstdc++6 libc6 libcap2-bin ssh adduser libyate5.2.0 yate-core madplay lame sox ntp nginx php-fpm php-cli php-db php-mysql

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
	#mysql -uroot -p$dbuserpw -e "SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));"

echo "Installer: Configuring web server..."
	pear install DB
	sed -i "s/NO_START=1/NO_START=0/" /etc/default/yate
	sed -i "s/# Required-Start:    \$remote_fs \$network/# Required-Start:    \$remote_fs \$network mysql php7.0-fpm/" /etc/init.d/yate
	sed -i "s/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/" /etc/php/7.0/fpm/php.ini
	sed -i "/types_hash_max_size/ a\ \t client_max_body_size 50m;" /etc/nginx/nginx.conf

#mysql config no_group_data
fe="/etc/mysql/my.cnf"
e="
[mysqld]
sql_mode = STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"
echo "$e" >> "$fe"

	#nginx www config
	fe="/etc/nginx/sites-available/default"
	e="server {
        listen 80 default_server;
        listen [::]:80 default_server;
        root /var/www;
        index index.php index.html index.htm;
        server_name kommunikator;
        location / {
                try_files \$uri \$uri/ =404;
        }
        location ~ \.php$ {
                include snippets/fastcgi-php.conf;
                fastcgi_pass unix:/run/php/php7.0-fpm.sock;
        }
        location ~ /\.ht {
                deny all;
        }
	}"
	if [ -e "$fe" ]; then
	cp $fe $fe"_komunikator.bak"
	fi
	echo "$e" > "$fe"

	#start page
	e="<meta http-equiv=\"refresh\" content=\"0;url=/kommunikator\">"
	echo "$e" > "/var/www/index.html"

echo "Installer: Copy web server..."
    rm -Rf /usr/share/yate/scripts/*
    cp ~/$repo_name/scripts/* /usr/share/yate/scripts -Rf
	mkdir -p /var/www/kommunikator
	cp ~/$repo_name/src/* /var/www/kommunikator -Rf
	cp ~/$repo_name/etc/* /etc -Rf
	cp ~/$repo_name/misc/* /var/lib/misc -Rf
	ln -s /var/lib/misc/auto_attendant /var/www/kommunikator/auto_attendant
	ln -s /var/lib/misc/moh /var/www/kommunikator/moh

echo "Installer: Trying to generate SSL certificate..."
	cert_dir="/etc/yate/keys"
	mkdir -p "${cert_dir}"

	crt_dir=${cert_dir}
	key_dir=${cert_dir}
	csr_dir=${cert_dir}
	mkdir -p "${key_dir}"
	mkdir -p "${crt_dir}"
	key="${key_dir}/komunikator.key"
	crt="${crt_dir}/komunikator.crt"
	csr="${csr_dir}/komunikator.csr"

	answers_csr() {
    	echo --
    	echo MariEl
    	echo Yoshkar-Ola
    	echo Komunikator.ru
    	echo dev
    	echo 127.0.0.1
    	echo support@komunikator.ru
    	echo ""
    	echo ""
	}

	# check if a new certificate should be generated
	# generate if certificate is already expired or if it will expire today
	replace=1
	if [ -f "${crt}" ]; then
        str=`openssl x509 -in ${crt} -enddate -noout`
        len=${#str}
        expr_date=`date -u -d"${str:9:len}"`
        now=`date -u`
        cmp_dates "${now}" "${expr_date}" && replace=0
	fi
	if [ "${replace}" = 1 ]; then
        # generate key file
        openssl genrsa -des3  -passout pass:freesentral -out "${key}" 1024  2> /dev/null
        echo "Generating SSL key"
        answers_csr | openssl req -new -passin pass:freesentral -key "${key}" -out "${csr}" 2> /dev/null
        echo "Generating SSL csr"
        cp "${key}" "${key}.orig"
        openssl rsa -passin pass:freesentral -in "${key}.orig" -out "${key}" 2> /dev/null
        openssl x509 -req -days 1825 -in "${csr}" -signkey "${key}" -out "${crt}" 2> /dev/null
        rm -f "${key}.orig"
        rm -f "${csr}"
	fi

echo "Installer: acsess rules..."
	mkdir -p /var/lib/misc/records/leg
	chown -R www-data:www-data /var/lib/misc/moh /var/lib/misc/auto_attendant
	chown -R yate:yate /var/lib/misc/records /var/lib/misc/records/leg
	chmod +x /usr/share/yate/scripts/*
	chmod 755 -R /var/lib/misc/records /var/lib/misc/records/leg
	echo "yate ALL = NOPASSWD: /sbin/iptables" >> /etc/sudoers

echo "Installer: restart service..."
	systemctl daemon-reload
	service yate stop
	service nginx reload
	service mysql restart
	service php7.0-fpm restart
	service yate start

rm -Rf $repo_name