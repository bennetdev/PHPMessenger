# Flash Messenger
An instant messenger written in HTML, Sass, Javascript and PHP. The messenger supports end-to-end encryption for messenges and files.

## Table of contents
* [General information](#general-info)
* [Setup](#setup)
* [Dependencies](#dependencies)
* [Features](#features)

## General information
Flash Messenger allows instant and encrypted messaging. The user's private key is stored encrypted with his password and a generated key. Additionally, you can see the online status of all your contacts and if they are currently writing to you.
### What is encrypted?
The content of a message and the data of files. Metadata is not being encrypted.

## Setup
1. Create the database "messenger" on your server by importing database.sql.
2. Install needed requirements 
3. Exchange MySQL credentials in files
4. run `php WsServer.php` on your server

## Dependencies
* PHP: 7.2.0
* cboden/ratchet: 0.4.3
* libsodium: 1.0.15

## Features
* end-to-end encryption
* Live updated online status for all users
* File and image sending up to 2MB (only restricted to save disk-space)
* Live updated writing status for selected user
