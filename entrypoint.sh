#!/bin/sh

# We need this script as we can't pass a list of files through Github
# Action argumements, but passes files as a single string. So we need
# this script to split the arguments before passing them to dais.
/src/dais $*
