#!/bin/bash
set -ex

aws acm request-certificate \
	--domain-name 'hcommons-dev.org' \
	--validation-method 'DNS' \
	--subject-alternative-names \
	'*.hcommons-dev.org' \
	'*.ajs.hcommons-dev.org' \
	'*.aseees.hcommons-dev.org' \
	'*.caa.hcommons-dev.org' \
	'*.mla.hcommons-dev.org' \
	'*.up.hcommons-dev.org' \
	'mlacommons.org' \
	'*.mlacommons.org' \
	'atwood.mlacommons.org' \
	'*.atwood.mlacommons.org' \
	'*.ajs.atwood.mlacommons.org' \
	'*.aseees.atwood.mlacommons.org' \
	'*.caa.atwood.mlacommons.org' \
	'*.mla.atwood.mlacommons.org' \
	'*.up.atwood.mlacommons.org' \
	'chaucer.mlacommons.org' \
	'*.chaucer.mlacommons.org' \
	'*.ajs.chaucer.mlacommons.org' \
	'*.aseees.chaucer.mlacommons.org' \
	'*.caa.chaucer.mlacommons.org' \
	'*.mla.chaucer.mlacommons.org' \
	'*.up.chaucer.mlacommons.org' \
	'heinlein.mlacommons.org' \
	'*.heinlein.mlacommons.org' \
	'*.ajs.heinlein.mlacommons.org' \
	'*.aseees.heinlein.mlacommons.org' \
	'*.caa.heinlein.mlacommons.org' \
	'*.mla.heinlein.mlacommons.org' \
	'*.up.heinlein.mlacommons.org' \
	'musashi.mlacommons.org' \
	'*.musashi.mlacommons.org' \
	'*.ajs.musashi.mlacommons.org' \
	'*.aseees.musashi.mlacommons.org' \
	'*.caa.musashi.mlacommons.org' \
	'*.mla.musashi.mlacommons.org' \
	'*.up.musashi.mlacommons.org' \
	'rumi.mlacommons.org' \
	'*.rumi.mlacommons.org' \
	'*.ajs.rumi.mlacommons.org' \
	'*.aseees.rumi.mlacommons.org' \
	'*.caa.rumi.mlacommons.org' \
	'*.mla.rumi.mlacommons.org' \
	'*.up.rumi.mlacommons.org' \
	'mla-dev.org' \
	'*.mla-dev.org'

echo 'now go click all the dns buttons'
