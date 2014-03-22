#!/bin/sh

VERSION=0.1.0
NAME=phpchat2

echo "make release"
find . -name .DS_Store -exec rm {} \;
mkdir -p releases
tar -cpf ${NAME}-${VERSION}.tar README.md bootstrap.php composer.json console.php cronjob.php functions.php kernel.php src vendor
gzip -9 -f ${NAME}-${VERSION}.tar
mv ${NAME}-${VERSION}.tar.gz releases
echo "done"
