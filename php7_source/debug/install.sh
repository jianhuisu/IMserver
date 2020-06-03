#!/bin/bash

sudo yum -y install libxml2 libxml2-devel
sudo yum -y install openssl openssl-devel
sudo yum -y install bzip2 bzip2-devel -y
sudo yum -y install curl curl-devel
sudo yum -y install libjpeglibjpeg -devel
sudo yum -y install libjpeg-devel
sudo yum -y install libpng libpng-devel
sudo yum -y install freetype-devel
sudo yum -y install libxslt libxslt-devel

yum  -y remove libzip-devel
wget https://libzip.org/download/libzip-1.3.2.tar.gz
tar xvf libzip-1.3.2.tar.gz
cd libzip-1.3.2
./configure
make && make install
cd ~
