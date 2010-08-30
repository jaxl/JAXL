#!/bin/bash
PACKAGE_INSTALL_PATH=/usr/share/php/jaxl
PACKAGE_BIN_PATH=/usr/bin

PACKAGE_NAME=JAXL
PACKAGE_VERSION=`awk '/^version/ {print $2; exit}' CHANGELOG`
PACKAGE_TAR_NAME=$PACKAGE_NAME-$PACKAGE_VERSION.tar.gz
PACKAGE_BASE=$PWD

case "$1" in
	help)
		echo "--------------------------------------"
		echo $PACKAGE_NAME" version "$PACKAGE_VERSION" build script"
		echo "--------------------------------------"
		echo "Usage: ./build.sh [param]"
		echo ""
		echo "Available params:"
		echo "help	To display help instructions"
		echo "install	To install Jaxl package"
		echo ""
		echo "Configuration options:"
		echo "PACKAGE_INSTALL_PATH	Base installation directory"
		echo "PACKAGE_BIN_PATH	Jaxl bin installation path"
		echo ""
		echo "Open/Edit the above configuration options inside build script"
		echo ""
	;;
        install)
		if [ -d $PACKAGE_INSTALL_PATH ];
		then
			echo "uninstalling old package..."
			cd $PACKAGE_INSTALL_PATH
			rm -R core
			rm -R xmpp
			rm -R xep
			rm -R env
			rm -R app/echobot
            rm -R app/componentbot
			rm -R app/boshchat
            rm -R app/boshMUChat
			rm $PACKAGE_BIN_PATH/jaxl
		else
			echo "creating package directories..."
			mkdir $PACKAGE_INSTALL_PATH
			cd $PACKAGE_INSTALL_PATH
                fi
		
		echo "installing..."
                tar -xvzf $PACKAGE_BASE/$PACKAGE_TAR_NAME
		ln -s $PACKAGE_INSTALL_PATH/env/jaxl.php $PACKAGE_BIN_PATH/jaxl
		chmod +x $PACKAGE_INSTALL_PATH/env/jaxl.php
        ;;
        *)
                echo "building..."
		tar -cvzf $PACKAGE_TAR_NAME \
			core/jaxl* \
			xmpp/xmpp* \
			xep/jaxl* \
			env/jaxl* \
			app/echobot/* \
            app/componentbot/* \
			app/boshchat/* \
            app/boshMUChat/*
        ;;
esac

exit 0
