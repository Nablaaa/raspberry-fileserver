# Raspberry Pi based Fileserver

This is a guid on setting up a home fileserver (like Google Drive) with the help of:
- [Nextcloud](https://nextcloud.com/de/)
- [Tailscale](https://login.tailscale.com/)
- [Docker](https://www.docker.com/)
- [Raspberry Pi 4](https://www.raspberrypi.com/products/raspberry-pi-4-model-b/) (more than 1 GB RAM recommended)
- [Hard Drive](amazon.de/-/en/dp/B07VC2TJK4?ref=ppx_yo2ov_dt_b_fed_asin_title) with USB 3 connection
- [Raspberry Pi 4 Cooling](https://www.amazon.de/Miuzei-Raspberry-Aluminium-W%C3%A4rmeleitenden-kompatibel/dp/B08HCDNP23?source=ps-sl-shoppingads-lpcontext&ref_=fplfs&smid=ADX1E4W4DEI4I&th=1)


This repo is a modified version of [nextcloud-ts git](https://github.com/avadhootabhyankar/nextcloud-ts.git). Modifications were necessary, to get the fileserver running. The main modification is to use the Raspberry Pi directly as connection point to Tailscale, instead of packing Tailscale into the docker container. This
approach makes the setup easier. Also, there are some performance optimizations in this repo here.


## Prerequisite
Please install Ubuntu on your [Raspberry Pi 4](https://www.raspberrypi.com/products/raspberry-pi-4-model-b/) to get started.

## Table of Content
1. Mount the hard drive to the Raspberry and prepare it for the docker
2. Set up the tailscale network
3. Install and set up Nextcloud via docker
4. Sign up in Nextcloud


## Raspberry Pi connection
You can either use the Raspberry Pi with a display connection and do the following steps in your Raspberry Pi console, or you can create a connection from another laptop via ssh.
For the ssh connection, you need the IP of your raspberry, which you can get in several ways.

- either use your laptop/computer to search in your network for the raspberry:
```bash
ping raspberrypi.local
# or sometimes
ping pi.local
```
- or go to your router's admin interface and search for the IP
- or connect the Raspberry with a display, start it and open the terminal. In the terminal, type the following to receive the IP address: 
```bash
hostname -I
```

To set up the SSH connection from your computer/laptop, you can open a terminal and type:

```bash
ssh name@XXX.XXX.X.XXX
```

All is fine. From now on, we will continue in the console of the Raspberry Pi. <br>

Be aware that the IP address of the Raspi can change if not set to static in your router's admin interface. If you want to put a static IP address, you have to go to your router's admin interface and assign a fixed IP to the Raspi MAC address. The admin interface can be reached via the link that is written on the router (e.g. Telekom uses for speedport: http://speedport.ip/html/login/index.html). The device password can be found on the router as well.

## Mount the hard drive
- connect the empty hard drive via USB 3.0 to your Raspberry Pi 4
- check for the partition (in this case, it is located at **sda1**)
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
sda           8:0    0 953.9G  0 disk 
└─sda1        8:1    0 953.9G  0 part 


.
.
.
```

- Format the partition to ext4 (do not format the disk)
```bash
sudo mkfs.ext4 /dev/sda1
```

- if this complains, that the drive is mounted, then unmount first:
```bash
sudo umount /dev/sda1
sudo mkfs.ext4 /dev/sda1
```

- now we want to always mount the drive to a defined location (files), in which we later on want to place our nextcloud installation and the database
```bash
sudo mkdir -p /mnt/files
sudo mount /dev/sda1 /mnt/files
```

- if this worked, you should see "lost+found" if you type 
```bash
ls /mnt/files
```

- the fileserver should always be starting automatically, even if the Raspi reboots, so we want to always mount the location sda1 to files on startup
```bash
sudo nano /etc/fstab
```
- inside the fstab document, type:
```bash
/dev/sda1 /mnt/files ext4 defaults 0 2
```
- or alternatively (recommended), use the UUID of the hard drive and mount it instead with:
```bash
UUID=abcd-1234 /mnt/files ext4 defaults,nofail,x-systemd.device-timeout=10 0 2
```
- you can get the UUID (format: 8-4-4-4-12)with:
```bash
blkid
```
- mount it and verify that it is mounted with:
```bash
sudo mount -a #--> expect no output
mount | grep /mnt/files # --> should show the UUID or the mount direction /dev/sda1 
```
- now we want to prepare 2 directories for a later step. We want to create a directory for the database and one for the nextcloud installation
```bash
sudo mkdir -p /mnt/files/docker-volumes/db
sudo mkdir -p /mnt/files/docker-volumes/nextcloud
```



## Raspberry modifications
If you plan to use the fileserver from several devices in parallel and perform multiple tasks on it in Nextcloud office, it is recommended to use ZRAM and SWAP memory to not run into out of memory errors. This step is optional, but helpful. But you can also do this step at a later point in time, if you run into out of memory issues.

Do the following in terminal of the Raspi (e.g. via SSH connection) to enable ZRAM:

```bash
# disable SD card swap
sudo swapoff /var/swap
sudo dphys-swapfile swapoff
sudo systemctl disable dphys-swapfile
```

```bash
# add zram
sudo apt update
sudo apt install zram-tools
sudo nano /etc/default/zramswap
```
now set the parameters:
```bash
ALGO=lz4
PERCENT=50
```
save and leave (CTRL+X then press "y" and ENTER)

```bash
# enable zram
sudo systemctl restart zramswap
# check
swapon --show
```

Now we continue with SWAP. We want to allocate 4 GB SWAP memory on the hard drive. Assume that the SSD is mounted to /mnt/files

```bash
sudo fallocate -l 4G /mnt/files/swapfile
sudo chmod 600 /mnt/files/swapfile
sudo mkswap /mnt/files/swapfile
sudo swapon /mnt/files/swapfile
# add to fstab
echo '/mnt/files/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab

# modify swappiness to 10 %
echo 'vm.swappiness=10' | sudo tee -a /etc/sysctl.conf
sudo sysctl -p

# check
free -h
```

You can now also prevent system crashes by installing an app that prevents out-of-memory errors. This app closes heavy apps early enough. The app is again optional:

```bash
sudo apt install earlyoom
sudo systemctl enable earlyoom
```

```bash
# final check
free -h
swapon --show
```


- now the hardware is ready and we want to set up the tailscale connection

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

You can modify the files with "nano". You can save the changes by using (CTRL+X then press "y" and ENTER). E.g.
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

- wait a bit and then check, if all docker containers are up with:
```bash
sudo docker ps
```

## Getting started with Nextcloud
Once your docker container is ready, you will be able to open nextcloud in your browser of choice with the raspberry-pi address given by tailscale. You can just open the webpage:
```bash
https://pi.tailXXXXXX.ts.net # add your correct address instead of XXXXXX
```
and then you will see the welcome screen of Nextcloud. If you get a Bad Gateway warning, then just wait a bit and reload the page.

Set a user name and a password and you are good to go. (Hint: the username and password should be different from the once set in the db.env / .env / config.php files).

If you want to make modifications on the upload settings (e.g. maximum upload size or maximum execution time), you can do this in the php-overrides.ini file. The specs can be seen on this page: https://pi.tailXXXXXX.ts.net/settings/admin/serverinfo.

If you want to have the fastest fileserver connection, please consider using a LAN connection instead of WLAN. Also note, that switching to LAN will change your Raspi IP address, in case you want to set up a tunnel via SSH again.


## Using Nextcloud office - Collabora
If you want to work with excel files or documents, you can go for nextcloud office, which requires a collabora server. This server is already running, if you have used the [docker compose file](docker-compose.yaml). You then just have to connect it to your nextcloud in the settings here: https://pi.tailXXXXXX.ts.net/settings/admin/richdocuments (replace XXXXXX). Use your own server, type in the URL https://pi.tailXXXXXX.ts.net and **Disable** the certificate verification (since we do not have a certificate). <br>

Now scroll down to **Advanced settings**. There, you should insert our "subnet" for WOPI requests, which you can receive from the Raspi terminal. For this, just type in the terminal:

```bash
docker network inspect nextcloud-ts_nextcloud-net | grep -i subnet
```
and insert the address "172.20.0.0/16" to the list and click "Save". Please make sure that you use this address in the [config.php file](conifg.php) for 'trusted_proxies'.
If you have to change this address, then please restart the docker with 


```bash
docker compose down
docker compose up -d
```


## Access your data straight from the hard drive
With a private server you know exactly where your data is stored. It is stored on your hard drive and can be accessed by everyone who has access to the hard drive. So for example, if you take the hard drive and insert it into your laptop, you can find the fileserver with:


```bash
lsblk
```

on the mountpoint: <br>
sda1           8:0    1   953.9G  0 disk /media/nablaaa/XXX

Then you can find the data in: 

```bash
sudo ls /media/nablaaa/XXX/docker-volumes/nextcloud/data/<nextcloud username>/files
```
and then you can copy the files to the Desktop and change access rights (writing access) if needed:

```bash
sudo cp -r /media/nablaaa/XXX/docker-volumes/nextcloud/data/<nextcloud username>/files /home/nablaaa/Desktop/

```


This is also one of the entry points for doing backups.



***
***


#TODO: 
- add image of the raspberry setup
- show how to do backups
