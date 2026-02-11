# Raspberry Pi based Fileserver

This is a guid on setting up a home fileserver (like Google Drive) with the help of:
- [Nextcloud]()
- [Tailscale]()
- [Docker]()
- [Raspberry Pi 4]()*
- [Hard Drive]()


#TODO: 
- fill in the links
- add image of the raspberry setup 


This repo is a modified version of [nextcloud-ts git](https://github.com/avadhootabhyankar/nextcloud-ts.git). Modifications were necessary, to get the fileserver running. The main modification is to use the Raspberry Pi directly as connection point to Tailscale, instead of packing Tailscale into the docker container. This
approach makes the setup easier.


## Prerequisite
Please install Ubuntu on your [Raspberry Pi 4]()* to get started.

## Table of Content
1. Mount the hard drive to the Raspberry and prepare it for the docker
2. Set up the tailscale network
3. Install and set up Nextcloud via docker
4. Sign up in Nextcloud

## Raspberry Pi connection
You can either use the Raspberry Pi with a display connection and do the following steps in your Raspberry Pi console, or you can create a connection from another laptop via ssh.
For the ssh connection, you need the IP of your raspberry, which you get with the command

```bash
hostname -I
```

Then you can do

```bash
ssh name@XXX.XXX.X.XXX
```

Both ways are fine. From now an, we will continue in the console of the Raspberry Pi. <br>
(Be aware, the IP address of the Raspi can change, if not set to static in your routers admin interface. But this will not be important for the following steps.)

## Mount hard drive
- connect the empty hard drive via USB 3.0 to your Raspberry Pi 4
- check for the drive (in this case, it is located at **sda**)
```bash
lsblk
```

```bash
NAME        MAJ:MIN RM  SIZE RO TYPE MOUNTPOINTS
loop0         7:0    0 93.3M  1 loop /snap/core/16204
loop1         7:1    0 92.8M  1 loop /snap/core/17276
.
.
.
loop10        7:10   0    6M  1 loop /snap/ngrok/122
sda           8:0    1  7.4G  0 disk 

.
.
.
```

- Format the drive to ext4 
```bash
sudo mkfs.ext4 /dev/sda
```

- if this complains, that the drive is mounted, then unmount first:
```bash
sudo umount /dev/sda
sudo mkfs.ext4 /dev/sda
```

- now we want to always mount the drive to a defined location (files), in which we later on want to place our nextcloud installation and the database
```bash
sudo mkdir -p /mnt/files
sudo mount /dev/sda /mnt/files
```

- if this worked, you should see "lost+found" if you type 
```bash
lsblk
```

- the fileserver should always be starting automatically, even if the Raspi reboots, so we want to always mount the location sda to files on startup
```bash
sudo nano /etc/fstab
```
- inside the fstab document, type:
```bash
/dev/sda /mnt/files ext4 defaults 0 2
```

- now we want to prepare 2 directories for a later step. We want to create a directory for the database and one for the nextcloud installation
```bash
sudo mkdir -p /mnt/files/docker-volumes/db
sudo mkdir -p /mnt/files/docker-volumes/nextcloud
```

- now this setup is done and we want to set up the tailscale connection

## Set up Tailscale
- install tailscale on the raspi and on one other device, with which you want to connect to the fileserver (e.g. laptop or phone)
```bash
sudo snap install curl
curl -fsSL https://tailscale.com/install.sh | sh
sudo tailscale up
```
- sign in with your preference
- once both devices (e.g. raspberry pi and laptop) are connected, you can do a communication test with ping
```bash
ping address -c 4
ping 100.115.XXX.XXX -c 4
```
- this should return
```bash
PING address (address) 56(84) bytes of data.
64 bytes from address: icmp_seq=1 ttl=64 time=54.6 ms
64 bytes from address: icmp_seq=2 ttl=64 time=77.4 ms
64 bytes from address: icmp_seq=3 ttl=64 time=0.831 ms
64 bytes from address: icmp_seq=4 ttl=64 time=76.2 ms
```
- and with this, tailscale is ready

## Install Nextcloud via Docker
In this part, we want to get nextcloud installed in a docker container. This needs a couple of configuration files, which can be downloaded from this GitHub repository. But first, we need docker:

```bash
sudo apt update && sudo apt upgrade -y
curl -fsSL https://get.docker.com | sudo sh
```

- now we can use the directories from the harddrive to bind our database and nextcloud
```bash
sudo docker volume create --driver local --opt type=none --opt device=/mnt/files/docker-volumes/db --opt o=bind db

sudo docker volume create --driver local --opt type=none --opt device=/mnt/files/docker-volumes/nextcloud --opt o=bind nextcloud
```

- give nextcloud processes file ownership for reading and writing files
```bash
sudo chown -R 33:33 /mnt/files/docker-volumes/nextcloud
```

- Now it is time to set up the configuration files for the docker container. For this, you can copy the files from this repository or just clone the repository to your raspberry pi
```bash
git clone https://github.com/Nablaaa/raspberry-fileserver.git

cd raspberry-fileserver  
```

- once you are in the folder, please modify the following files:
    - config.php
    - db.env
    - .env

You can modify the files with "nano". You can save the changes by using CTRL+X and "y" + ENTER. E.g.
```bash
nano config.php
```

Let's go through them step by step, starting with the db.env and .env files. In the db.env file, you just have to add 2 proper passwords. In the .env file, you have to create 1 password for redis.<br>
In the config.php file, you have to generate 2 strings for 'passwordsalt' and for 'secret'. Here, you can use the command line:
```bash
openssl rand -base64 48
```
to create a string that is sufficient. Also, all constructions with "pi.tailXXXXXX.ts.net" have to be replaced by the proper address, which you find in your [tailscale machines](https://login.tailscale.com/admin/machines) by clicking on the arrow next to the address of the device. Make sure, that you use the address of your raspberry pi and not of any other device.

- Now you can start the docker container with
```bash
sudo docker compose up -d
```

- check, if the docker is up with:
```bash
sudo docker ps
```

## Getting started with Nextcloud
Once your docker container is ready, you will be able to open nextcloud with the raspberry-pi address given by tailscale. You can just open the page:
```bash
https://<address>
https://pi.tailXXXXXX.ts.net
```
and then you will see the welcome screen of Nextcloud. Set a user name and a password and you are good to go.

If you want to make modifications on the upload settings (e.g. maximum upload size or maximum execution time), you can do this in the php-overrides.ini file. The specs can be seen on this [page](https://pi.tail03fe67.ts.net/settings/admin/serverinfo).



*affiliate link - if you click on this link and purchase the product, you will directly support my work