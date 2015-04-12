#!/bin/sh
#
# scaling to x by y:
#
# 1) create background by scaling so that it completely fills the page and then x2? and blur
# 2) create foreground by scaling to X x Y x 90%? 
# 3) superimpose 2 over 1
#
width=1920
height=1080
size=$width\x$height
bg="$1\.bg"
fg="$1\.fg"

convert "$1" \
  -resize $size^ \
  -gravity center \
  -distort SRT 1.5,0 \
  -extent $size  \
  -blur 0x16 \
  -fill black -colorize 50% \
  "$bg"

convert "$1" \
  -resize $size \
  "$fg"

composite \
  -compose over \
  -gravity center \
  "$fg" "$bg" \
  "$2"

rm -f "$fg" "$bg"
