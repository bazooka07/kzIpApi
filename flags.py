#!/usr/bin/env python3
# -*- coding: utf-8 -*-

from math import sqrt, ceil
from PIL import Image
from os import listdir
from os.path import join, splitext

PATH = '../../core/assets/img/flags/32'
SIZE = 32
BASENAME = 'kz-flag'
NAME = 'flags'

def isImage(fn):
	try:
		im = Image.open(join(PATH, fn))
		return True
	except:
		return False

def main(args):

	html = '''<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<meta http-equiv="content-type" content="text/html;charset=utf-8" />
	<title>Flags</title>
	<style>
		body { background-color: #444; }
		body > div { display: flex; flex-wrap: wrap; }
		body > div div {
		    margin: 5px;
		    background: #e8e8e8;
		    text-align: center;
		    padding: 0 2px;
		}
	</style>
	<link rel="stylesheet" href="%s.css" />
</head>
<body>
<div>
''' % NAME
	css = '''
.%s {
	display: block;
	width: %dpx;
	height: %dpx;
	background-image: url('%s.png');
}
''' % (BASENAME, SIZE, SIZE, NAME)

	files = [f for f in listdir(PATH) if isImage(f)]
	xMax = int(sqrt(len(files)) + 1)
	yMax = ceil(len(files) / xMax)
	sprite = Image.new("RGBA", (SIZE * xMax , SIZE * yMax))
	for i, f in enumerate(files):
		im = Image.open(join(PATH, f))
		col = SIZE * (i % xMax)
		row = SIZE * int(i / xMax)
		(fn, ext) = splitext(f)
		sprite.paste(im, (col, row))
		css += ".%s.%s { background-position: %4dpx %4dpx; }\n" % (BASENAME, fn, -col, -row)
		html +=  '<div><span class="%s %s">&nbsp;</span><span>%s</span></div>\n' %(BASENAME, fn, fn)
	html += '''</div>
</body>
</html>
'''
	sprite.save('%s.png' % NAME, 'png')
	with open('%s.html' % NAME, 'w') as f:
		f.write(html)
	with open('%s.css' % NAME, 'w') as f:
		f.write(css)
	return 0

if __name__ == '__main__':
    import sys
    sys.exit(main(sys.argv))