# PHPChat
A decentralized, peer-to-peer, encrypted chat in PHP.

## Why this project?
Because we need to encrypt and secure the Internet. The Internet is broken and we need to fix it. Thanks people like [Edward Snowden](https://en.wikipedia.org/wiki/Edward_Snowden) we know today that the [NSA](https://en.wikipedia.org/wiki/National_Security_Agency) (and also other [intelligence agencies](https://en.wikipedia.org/wiki/Intelligence_agency) too) operates a [global surveillance](https://en.wikipedia.org/wiki/Global_surveillance_disclosures_(2013%E2%80%93present)) on citizens. We can't loose our right of freedom, our right of privacy to centralized governmental authorities. Now it's our move. It's time to fight back!

I also like the [Bitmessage](https://bitmessage.org) project. But building thinks by myself feels like I can contribute to the Internet. And coding this with PHP because it's an easy-to-learn programming language and every one can [contribute](#contribute) to this project.

## Features
- [Peer-to-peer](http://en.wikipedia.org/wiki/Peer-to-peer) instant messaging.
- Decentralized: See [DHT](http://en.wikipedia.org/wiki/Distributed_hash_table) and [Kademlia](http://en.wikipedia.org/wiki/Kademlia).
- Encryption: SSL
- Send P2P random messages.
- Addressbook: manage all conversation partners.
- [IMAP](https://github.com/TheFox/imapd) interface for fetching new messages.
- [SMTP](https://github.com/TheFox/smtpd) interface for sending messages.

## Install
1. Clone

		git clone https://github.com/TheFox/phpchat.git

2. Change to your `phpchat` directory and run

		make

3. You must forward TCP port 25000 (default) on your modem to your computer. After the chat has been started once there will be a `settings.yml`. Edit this file to change the incoming port. After changing the settings file you must restart the chat.
4. Run

		./start.sh
	
	in your shell to start the PHPChat. And
	
		./stop.sh
	
	to stop the PHPChat.


## Contribute
You're welcome to contribute to this project. Fork this project at <https://github.com/TheFox/phpchat>. You should read GitHub's [How to Fork a Repo](https://help.github.com/articles/fork-a-repo).

## License
Copyright (C) 2014 Christian Mayer <http://fox21.at>

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details. You should have received a copy of the GNU General Public License along with this program. If not, see <http://www.gnu.org/licenses/>.
