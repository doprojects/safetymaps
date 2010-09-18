all: a4-4up.pdf a4-2up.pdf a4-poster.pdf letter-4up.pdf letter-2up.pdf letter-poster.pdf
	#

a4-4up.pdf:
	mv -v `python compose.py -p a4 -f 4up` a4-4up.pdf

a4-2up.pdf:
	mv -v `python compose.py -p a4 -f 2up-fridge` a4-2up.pdf

a4-poster.pdf:
	mv -v `python compose.py -p a4 -f poster` a4-poster.pdf

letter-4up.pdf:
	mv -v `python compose.py -p letter -f 4up` letter-4up.pdf

letter-2up.pdf:
	mv -v `python compose.py -p letter -f 2up-fridge` letter-2up.pdf

letter-poster.pdf:
	mv -v `python compose.py -p letter -f poster` letter-poster.pdf

clean:
	rm -f a4-4up.pdf a4-2up.pdf a4-poster.pdf
	rm -f letter-4up.pdf letter-2up.pdf letter-poster.pdf