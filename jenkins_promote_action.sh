#!/bin/bash

# switch ruby version
source ~/.bash_profile
rvm use 2.0.0@jenkins

# Determine ipa name
ipa=$(tail -n 1 "${WORKSPACE}/Build/logfile.txt"|sed -e "s/.*--ipa '\([^']*\)'.*/\1/")
artifact=$(basename -s .ipa "${ipa}")

cd "${WORKSPACE}/../builds/lastSuccessfulBuild"
"${HOME}/Scripts/logparser.rb" >"${WORKSPACE}/Build/Products/Debug-iphoneos/${artifact}.html"