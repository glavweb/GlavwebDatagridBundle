#!/bin/bash
set -x
shopt -s dotglob

cd ..

rm -rf build/*
cp -r app/* build