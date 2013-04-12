#!/bin/bash

# switch ruby version
source ~/.bash_profile
rvm use 2.0.0@jenkins

cd ${WORKSPACE}

set -x
bundle install

ruby ${WORKSPACE}/build.rb --id-file /Users/<user>/Cert/identity.p12 --id-password <passwort> --profile /Users/<user>/Cert/team.mobileprovision