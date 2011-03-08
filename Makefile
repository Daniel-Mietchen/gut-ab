TARGET=ab

all: ${TARGET}.bib ${TARGET}.tex ${TARGET}.pdf

${TARGET}.pdf: ${TARGET}.bbl ${TARGET}.tex
	@pdflatex -interaction=nonstopmode ${TARGET}.tex

${TARGET}.bbl: ${TARGET}.bib ${TARGET}.tex
	@rm -f ${TARGET}.bbl
	@pdflatex -interaction=nonstopmode ${TARGET}.tex
	@bibtex ${TARGET}
	@pdflatex -interaction=nonstopmode ${TARGET}.tex
	@pdflatex -interaction=nonstopmode ${TARGET}.tex

${TARGET}.tex::
	@php abexport.php > ${TARGET}.tex

${TARGET}.bib::
	@php bibTeXexport.php > ${TARGET}.bib

clean:
	@rm -f ${TARGET}.log
	@rm -f ${TARGET}.aux
	@rm -f ${TARGET}.bbl
	@rm -f ${TARGET}.blg
	@rm -f ${TARGET}.log
	@rm -f ${TARGET}.toc
	@rm -f ${TARGET}.idx
	@rm -f ${TARGET}.ilg
	@rm -f ${TARGET}.ind
	@rm -f ${TARGET}.thm
	@rm -f ${TARGET}.out
	@rm -f texput.log
