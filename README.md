# PHPChat2
A decentralized, peer-to-peer, encrypted chat in PHP.

## Features
- [Peer-to-peer](http://en.wikipedia.org/wiki/Peer-to-peer) instant messaging.
- Decentralized: See [DHT](http://en.wikipedia.org/wiki/Distributed_hash_table) and <http://bittorrent.org/beps/bep_0005.html>.
- Encryption: SSL
- Send P2P random messages.
- Addressbook: manage all conversation partners.

## Install
1. Clone

	`git clone https://github.com/TheFox/phpchat2.git`

2. Change to your `phpchat2` directory and run

	`composer install`

3. You must forward TCP port 25000 (default) on your modem to your computer. After the chat has been started once there will be a `settings.yml`. Edit this file to change the incoming port.

## Dependencies
Before running PHPChat, make sure you have all the needed dependencies
installed on your system.

Here's a list of dependencies needed for PHPChat:

- PHP >= 5.3
- [Composer](https://getcomposer.org/)

## ToDo
- Hashcash on connect. [link_1](http://en.wikipedia.org/wiki/Hashcash) | [link_2](https://en.bitcoin.it/wiki/Hashcash)
- Forward msgs to a uuid through another node. (relay)
- Supernode over HTTP: no active process; just a http request, json interface. No GUI.
- IMAP-server interface.
- ReSSL after period/number of msgs. Reset the SSL passwords.
- SSL sign public key to prove peer holds the private key.

## Contribute
You're welcome to contribute to this project. Fork this project at <https://github.com/TheFox/phpchat2>. You should read GitHub's [How to Fork a Repo](https://help.github.com/articles/fork-a-repo).

## License
Copyright (C) 2014 Christian Mayer (<thefox21at@gmail.com> - <http://fox21.at>)

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details. You should have received a copy of the GNU General Public License along with this program. If not, see <http://www.gnu.org/licenses/>.
