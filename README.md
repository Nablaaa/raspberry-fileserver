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

- the fileserver should always be starting automatically, even if the Raspi reboots, so we want to permanently mount the hard drive to the files location


# TODO continue here

Once finished, we want to create 2 locations on the hard drive. In one location, we want to store all data. The other location is needed for the Nextcloud installation.



## Set up Tailscale


## Install Nextcloud


## Getting started with Nextcloud





*affiliate link - if you click on this link and purchase the product, you will directly support my work