<?php
switch ( $textpart ) {
	//browsesources.php, showsource.php
	case "sources":
		$text['browseallsources'] = "Skoða allar heimildir";
		$text['shorttitle'] = "Stuttur titill";
		$text['callnum'] = "Símanúmer";
		$text['author'] = "Höfundur";
		$text['publisher'] = "Útgefandi";
		$text['other'] = "Aðrar upplýsingar";
		$text['sourceid'] = "Nr. heimildar";
		$text['moresrc'] = "Fleiri Heimildir";
		$text['repoid'] = "Nr. heimildasafns";
		$text['browseallrepos'] = "Skoða öll heimildasöfn";
		break;

	//changelanguage.php, savelanguage.php
	case "language":
		$text['newlanguage'] = "Nýtt tungumál";
		$text['changelanguage'] = "Breyta tungumáli";
		$text['languagesaved'] = "Tungumál geymt";
		$text['sitemaint'] = "viðhald í gangi á vefsíðunni";
		$text['standby'] = "Síðan er ekki aðgengileg í augnablikinu vegna viðhalds á gagnagrunni. Vinsamlegast reynið aftur eftir nokkrar mínútur. Ef síðan er niðri í lengri tíma, vinsamlegast <a href=\"suggest.php\"> hafið samband við vefstjóra</a>.";
		break;

	//gedcom.php, gedform.php
	case "gedcom":
		$text['gedstart'] = "GEDCOM byrjar frá";
		$text['producegedfrom'] = "útbúa GEDCOM skrá frá";
		$text['numgens'] = "fjöldi kynslóða";
		$text['includelds'] = "hafa LDS upplýsingar með";
		$text['buildged'] = "Byggja GEDCOM";
		$text['gedstartfrom'] = "GEDCOM byrjar frá";
		$text['nomaxgen'] = "Þú verður að skilgreina fjölda kynslóða. Vinsamlegast notið back takkan til að fara til baka";
		$text['gedcreatedfrom'] = "GEDCOM skapað af";
		$text['gedcreatedfor'] = "Skapað fyrir";
		$text['creategedfor'] = "útbúa GEDCOM fyrir";
		$text['email'] = "Netfang";
		$text['suggestchange'] = "Stinga uppá breytingu";
		$text['yourname'] = "Nafn þitt";
		$text['comments'] = "Skilaboð eða athugasemdir";
		$text['comments2'] = "Athugasemdir";
		$text['submitsugg'] = "Senda inn uppástungu";
		$text['proposed'] = "Breyting";
		$text['mailsent'] = "Takk fyrir. Skilaboð þín hafa verið send.";
		$text['mailnotsent'] = "Því miður komust skilaboðin ekki til. Vinsamlegast hafðu samband við xxx beint yyy.";
		$text['mailme'] = "Senda afrit á þetta netfang";
		$text['entername'] = "Vinsamlegast sláðu inn nafnið þitt";
		$text['entercomments'] = "Vinsamlegast sláðu inn athugasemdir";
		$text['sendmsg'] = "Senda skilaboð";
		//added in 9.0.0
		$text['subject'] = "Efni";
		break;

	//getextras.php, getperson.php
	case "getperson":
		$text['photoshistoriesfor'] = "Ljósmyndir og saga fyrir";
		$text['indinfofor'] = "Einstaklings upplýsingar fyrir";
		$text['pp'] = "bls."; //page abbreviation
		$text['age'] = "Aldur";
		$text['agency'] = "Samtök";
		$text['cause'] = "Ástæða";
		$text['suggested'] = "Uppástunga";
		$text['closewindow'] = "Loka þessum glugga";
		$text['thanks'] = "Takk fyrir";
		$text['received'] = "Uppástungu hefur verið komið áleiðis til vefstjóra til skoðunar.";
		$text['indreport'] = "Einstök skýrsla";
		$text['indreportfor'] = "Individual Report for";
		$text['bkmkvis'] = "<strong>Ath:</strong> Þessi bókamerki eru einungis sýnileg í þessari tölvu og í þessum vafra.";
        //added in 9.0.0
		$text['reviewmsg'] = "Þú þarft að skoða athugasemd um breytingu sem þér hefur verið send.  Þessi athugasemd varðar:";
        $text['revsubject'] = "Eftirfarandi athugasemd bíður skoðunar";
        break;

	//relateform.php, relationship.php, findpersonform.php, findperson.php
	case "relate":
	case "connections":
		$text['relcalc'] = "Skyldleika reiknivél";
		$text['findrel'] = "Rekja saman";
		$text['person1'] = "Einstaklingur 1:";
		$text['person2'] = "Einstaklingur 2:";
		$text['calculate'] = "Reikna";
		$text['select2inds'] = "Veldu 2 einstaklinga.";
		$text['findpersonid'] = "Finna númer einstaklings";
		$text['enternamepart'] = "Sláðu inn hluta fyrra nafns/eða seinna nafns";
		$text['pleasenamepart'] = "Vinsamlegast sláðu inn hluta nafns.";
		$text['clicktoselect'] = "Smelltu hér til að velja";
		$text['nobirthinfo'] = "Engar upllýsingar um fæðingardag";
		$text['relateto'] = "Skyldleiki til";
		$text['sameperson'] = "Þetta er sama manneskjan.";
		$text['notrelated'] = "Þessir 2 einstaklingar eru ekki skyldir innan xxx kynslóða."; //xxx will be replaced with number of generations
		$text['findrelinstr'] = "Sláðu inn nr. einstaklings, eða notaðu Finna takkann, smelltu svo á Reikna til að finna skyldleika þessara tveggja einstaklinga (allt að xxx kynslóðir).";
		$text['sometimes'] = "(Stundum þegar athugað er yfir mismunandi fjölda kynslóða kemur önnur niðurstaða.)";
		$text['findanother'] = "Finna annan skyldleika";
		$text['brother'] = "Bróðir";
		$text['sister'] = "Systir";
		$text['sibling'] = "Systkyni";
		$text['uncle'] = "xxx frændi";
		$text['aunt'] = "xxx frænka";
		$text['uncleaunt'] = "xxx frændur/frænkur";
		$text['nephew'] = "xxx frændi";
		$text['niece'] = "xxx frænka";
		$text['nephnc'] = "xxx frændur/frænkur";
		$text['removed'] = "oft fjarlæður";
		$text['rhusband'] = "eiginmaður ";
		$text['rwife'] = "eiginkona ";
		$text['rspouse'] = "maki ";
		$text['son'] = "sonur";
		$text['daughter'] = "dóttir";
		$text['rchild'] = "barn";
		$text['sil'] = "Tengdasonur";
		$text['dil'] = "Tengdadóttir";
		$text['sdil'] = "Tengdasonur eða dóttir";
		$text['gson'] = "Barnabarn";
		$text['gdau'] = "xxx barnabarn";
		$text['gsondau'] = "xxx barnabarn";
		$text['great'] = "langa";
		$text['spouses'] = "eru makar";
		$text['is'] = "er";
		$text['changeto'] = "Breyta í:";
		$text['notvalid'] = "er ekki gilt sem nr. einstaklings eða er ekki til i gagnagrunninum.  Vinsamlegast reynið aftur.";
		$text['halfbrother'] = "hálfbróðir";
		$text['halfsister'] = "hálfsystir";
		$text['halfsibling'] = "hálf systkyni";
		//changed in 8.0.0
		$text['gencheck'] = "Hámarks fjöldi kynslóða<br />sem skoða skal";
		$text['mcousin'] = "xxx frændi yyy";  //male cousin; xxx = cousin number, yyy = times removed
		$text['fcousin'] = "xxx frændi yyy";  //female cousin
		$text['cousin'] = "xxx frændi yyy";
		$text['mhalfcousin'] = "the xxx half cousin yyy of";  //male cousin
		$text['fhalfcousin'] = "the xxx half cousin yyy of";  //female cousin
		$text['halfcousin'] = "the xxx half cousin yyy of";
		//added in 8.0.0
		$text['oneremoved'] = "one removed";
		$text['gfath'] = "xxx langafi af";
		$text['gmoth'] = "xxx langamma af";
		$text['gpar'] = "xxx afit of";
		$text['mothof'] = "mamma af";
		$text['fathof'] = "faðir af";
		$text['parof'] = "foreldri of";
		$text['maxrels'] = "Sýnilegur hámarks skyldleiki";
		$text['dospouses'] = "Sýna skyldleika í gegnum maka";
		$text['rels'] = "Skyldleikar";
		$text['dospouses2'] = "Sýna maka";
		$text['fil'] = "tengdafaðir";
		$text['mil'] = "tengdamóðir";
		$text['fmil'] = "tengdaforeldrar";
		$text['stepson'] = "stjúpsonur";
		$text['stepdau'] = "stjúpdóttir";
		$text['stepchild'] = "stjúpbarn";
		$text['stepgson'] = "stjúp-sonarsonur hans";
		$text['stepgdau'] = "stjúp-dóttursonur";
		$text['stepgchild'] = "stjúp barnabarn";
		//added in 8.1.1
		$text['ggreat'] = "langa";
		//added in 8.1.2
		$text['ggfath'] = "xxx langafi";
		$text['ggmoth'] = "xxx langamma";
		$text['ggpar'] = "the xxx great grandparent of";
		$text['ggson'] = "xxx barnabarnabarn";
		$text['ggdau'] = "the xxx great granddaughter of";
		$text['ggsondau'] = "the xxx great grandchild of";
		$text['gstepgson'] = "the xxx great step-grandson of";
		$text['gstepgdau'] = "the xxx great step-granddaughter of";
		$text['gstepgchild'] = "the xxx great step-grandchild of";
		$text['guncle'] = "the xxx great uncle of";
		$text['gaunt'] = "the xxx great aunt of";
		$text['guncleaunt'] = "the xxx great uncle/aunt of";
		$text['gnephew'] = "the xxx great nephew of";
		$text['gniece'] = "the xxx great niece of";
		$text['gnephnc'] = "the xxx great nephew/niece of";
		//added in 14.0
		$text['pathscalc'] = "Leita að tengingum";
		$text['findrel2'] = "Rekja saman";
		$text['makeme2nd'] = "Use my ID";
		$text['usebookmarks'] = "Nota bókamerki";
		$text['select2inds'] = "Veldu 2 einstaklinga.";
		$text['indinfofor'] = "Einstaklings upplýsingar fyrir";
		$text['nobookmarks'] = "Það er ekkert bókamerki til";
		$text['bkmtitle'] = "Einstaklingar fundnir í bókamerkjum";
		$text['bkminfo'] = "Velja einstakling:";
		$text['sortpathsby'] = "Sort paths by number of";
		$text['sortbyshort'] = "Raða eftir";
		$text['bylengthshort'] = "Lengd";
		$text['badID1'] = ": bad person1 ID - please go back and correct";
		$text['badID2'] = ": bad person2 ID - please go back and correct";
		$text['notintree'] = ": the person with this ID is not in the current tree database.";
		$text['sameperson'] = "Þetta er sama manneskjan.";;
		$text['nopaths'] = "Þessir einstaklingar eru ekki tengdir.";
		$text['nopaths1'] = "No more connections shorter than xxx";
		$text['nopaths2'] = "í xxx leitarskrefum";
		$text['longestpath'] = "(longest path checked so far was xxx steps long)";
		$text['relevantpaths'] = "The number of different relevant paths found: xxx";
		$text['skipMarr'] = "(in addition, the number of paths found but not displayed due to too many marriages was: xxx)";
		$text['mjaor'] = "or";
		$text['connectionsto'] = "Connections to ";
		$text['findanotherpers'] = "Finna annan einstakling...";
		$text['sometimes'] = "(Stundum þegar athugað er yfir mismunandi fjölda kynslóða kemur önnur niðurstaða.)";
		$text['anotherpath'] = "Leita að öðrum tengingum";
		$text['xpath'] = "Leið ";
		$text['primary'] = "Start Person"; // note: used for both Start and End if text['fin'] not set
		$text['secondary'] = "End Person";
		$text['parent'] = "Foreldri";
		$text['mhfather'] = "faðir hans";
		$text['mhmother'] = "móðir hans";
		$text['mhhusband'] = "eiginmaður hans";
		$text['mhwife'] = "eiginkona hans";
		$text['mhson'] = "sonur hans";
		$text['mhdaughter'] = "dóttir hans";
		$text['fhfather'] = "faðir hennar";
		$text['fhmother'] = "móðir hennar";
		$text['fhhusband'] = "eiginmaður hennar";
		$text['fhwife'] = "eiginkona hennar";
		$text['fhson'] = "sonur hennar";
		$text['fhdaughter'] = "dóttir hennar";
		$text['hfather'] = "faðir";
		$text['hmother'] = "móðir";
		$text['hhusband'] = "eiginmaður";
		$text['hwife'] = "eiginkona";
		$text['hson'] = "sonur";
		$text['hdaughter'] = "dóttir";
		$text['maxruns'] = "Max number of paths to be checked";
		$text['maxrshort'] = "Max paths";
		$text['maxlength'] = "Connection paths no longer than";
		$text['maxlshort'] = "Hámarkslengd";
		$text['xstep'] = "skref";
		$text['xsteps'] = "skref";
		$text['xmarriages'] = "xxx hjónabönd";
		$text['xmarriage'] = "1 hjónaband";
		$text['showspouses'] = "Sýna báða maka";
		$text['showTxt'] = "Show textual path description";
		$text['showTxtshort'] = "Textual desc.";
		$text['compactBox'] = "Show person boxes compacted";
		$text['compactBoxshort'] = "Compact boxes";
		$text['paths'] = "Paths";
		$text['dospouses2'] = "Sýna maka";
		$text['maxmopt'] = "Hámarksfjöldi hjónabanda í tengingu";
		$text['maxm'] = "Hámarksfjöldi hjónabanda";
		$text['arerelated'] = "Þessir einstaklingar eru skyldir - tenging þeirra er sýnd á Leið 1";
		$text['simplerel'] = "Einföld tengsla leit";
		break;

	case "familygroup":
		$text['familygroupfor'] = "Fjölskyldu blað fyrir";
		$text['ldsords'] = "LDS Ordinances";
		$text['baptizedlds'] = "Skírður (LDS)";
		$text['endowedlds'] = "Fermdur (LDS)";
		$text['sealedplds'] = "Sealed P (LDS)";
		$text['sealedslds'] = "Sealed S (LDS)";
		$text['otherspouse'] = "Annar/ur maki";
		$text['husband'] = "Eiginmaður";
		$text['wife'] = "Eiginkona";
		break;

	//pedigree.php
	case "pedigree":
		$text['capbirthabbr'] = "F";
		$text['capaltbirthabbr'] = "A";
		$text['capdeathabbr'] = "D";
		$text['capburialabbr'] = "G";
		$text['capplaceabbr'] = "S";
		$text['capmarrabbr'] = "G";
		$text['capspouseabbr'] = "SP";
		$text['redraw'] = "Enduraða með";
		$text['unknownlit'] = "Óskráð";
		$text['popupnote1'] = " = Frekari upplýsingar";
		$text['pedcompact'] = "Þjappað";
		$text['pedstandard'] = "Staðlað";
		$text['pedtextonly'] = "Einungis texti";
		$text['descendfor'] = "Afkomendur";
		$text['maxof'] = "Hámark af";
		$text['gensatonce'] = "kynslóðir birtar í einu.";
		$text['sonof'] = "foreldrar:";
		$text['daughterof'] = "foreldrar:";
		$text['childof'] = "barn";
		$text['stdformat'] = "Staðlað snið";
		$text['ahnentafel'] = "Framætt";
		$text['addnewfam'] = "Bæta við fjölskyldu";
		$text['editfam'] = "Breyta fjölskyldu";
		$text['side'] = "Hlið";
		$text['familyof'] = "Fjölskylda";
		$text['paternal'] = "Föður";
		$text['maternal'] = "Móður";
		$text['gen1'] = "Sjálf/ur";
		$text['gen2'] = "Foreldrar";
		$text['gen3'] = "Ömmur og afar";
		$text['gen4'] = "Langömmur og afar";
		$text['gen5'] = "Í annan ættlið";
		$text['gen6'] = "Í þriðja ættlið";
		$text['gen7'] = "Í fjórða ættlið";
		$text['gen8'] = "Í fimmta ættlið";
		$text['gen9'] = "Í sjötta ættlið";
		$text['gen10'] = "Í Sjöunda ættlið";
		$text['gen11'] = "Í áttunda Ættlið";
		$text['gen12'] = "Í níunda ættlið";
		$text['graphdesc'] = "Grafískt niðjatal að þessum punkti";
		$text['pedbox'] = "Kassar";
		$text['regformat'] = "Niðjatal";
		$text['extrasexpl'] = "Ef að ljósmyndir eða sögur eru til um viðkomandi einstakling, koma viðeigandi myndir við nöfnin.";
		$text['popupnote3'] = " = Nýtt ";
		$text['mediaavail'] = "Margmiðlun tiltæk";
		$text['pedigreefor'] = "Niðjatal fyrir";
		$text['pedigreech'] = "Ættarbókarmynd";
		$text['datesloc'] = "Dagsetningar og staðsetningar";
		$text['borchr'] = "Fæddur/Eða - dáin/jarðaður (tveir)";
		$text['nobd'] = "Engin fæðingar- eða dánardagur";
		$text['bcdb'] = "Fæddur/Alt/dánar/jarðaður (four)";
		$text['numsys'] = "Númera kerfi";
		$text['gennums'] = "Kynslóðar númer";
		$text['henrynums'] = "Henry númer";
		$text['abovnums'] = "d'Aboville númer";
		$text['devnums'] = "de Villiers Nnúmer";
		$text['dispopts'] = "Valkostir";
		//added in 10.0.0
		$text['no_ancestors'] = "Engir forfeður fundust";
		$text['ancestor_chart'] = "Lóðrétt ættartré (áar)";
		$text['opennewwindow'] = "Opna í nýjum glugga";
		$text['pedvertical'] = "Lóðrétt";
		//added in 11.0.0
		$text['familywith'] = "Family with";
		$text['fcmlogin'] = "Vinsamlegast skráðu þig inn til að skoða nánar";
		$text['isthe'] = "er";
		$text['otherspouses'] = "aðrir makar";
		$text['parentfamily'] = "The parent family ";
		$text['showfamily'] = "Sýna fjölskyldu";
		$text['shown'] = "shown";
		$text['showparentfamily'] = "show parent family";
		$text['showperson'] = "sýna einstakling";
		//added in 11.0.2
		$text['otherfamilies'] = "Other families";
		//added in 14.0
		$text['dtformat'] = "Töflur";
		$text['dtchildren'] = "Börn";
		$text['dtgrandchildren'] = "Barnabörn";
		$text['dtggrandchildren'] = "Barnabarnabörn";
		$text['dtgggrandchildren'] = "Barnabarnabörn"; //For 2x great grandchildren, 3x great grandchildren, etc. Usually different in Scandinavian languages
		$text['greatoffset'] = "0"; //Scandinavian languages should set this to 1 so counting starts a generation later
		$text['dtnodescendants'] = "Engir afkomendur";
		$text['dtgen'] = "Gen";
		$text['dttotal'] = "Samtals";
		$text['dtselect'] = "Velja";
		$text['dteachfulltable'] = "Each full table will have";
		$text['dtrows'] = "raðir";
		$text['dtdisplayingtable'] = "Displaying table";
		$text['dtgototable'] = "Go to table:";
		$text['fcinstrdn'] = "Sýna fjölskyldu með maka";
		$text['fcinstrup'] = "Sýna fjölskyldu með foreldrum";
		$text['fcinstrplus'] = "Velja aðra maka";
		$text['fcinstrfam'] = "Velja aðra foreldra";
		break;

	//search.php, searchform.php
	//merged with reports and showreport in 5.0.0
	case "search":
	case "reports":
		$text['noreports'] = "Engar skýrslur til.";
		$text['reportname'] = "Nafn skýrslu";
		$text['allreports'] = "Allar skýrslur";
		$text['report'] = "Skýrslur";
		$text['error'] = "Villa";
		$text['reportsyntax'] = "Eitthvað við þessa fyrirspurn";
		$text['wasincorrect'] = "var rangt, og gátum við ekki séð skýrsluna. vinsemlagast hafið samband við vefstjóra á";
		$text['errormessage'] = "Villu boð";
		$text['equals'] = "er";
		$text['endswith'] = "endar á";
		$text['soundexof'] = "soundex of";
		$text['metaphoneof'] = "metaphone of";
		$text['plusminus10'] = "+/- 10 árum frá";
		$text['lessthan'] = "minna en";
		$text['greaterthan'] = "meira en";
		$text['lessthanequal'] = "minna en eða jafnt og";
		$text['greaterthanequal'] = "meira en eða jafnt og";
		$text['equalto'] = "Jafnt og";
		$text['tryagain'] = "Vinsamlegast reyndu aftur";
		$text['joinwith'] = "Sameina með";
		$text['cap_and'] = "og";
		$text['cap_or'] = "eða";
		$text['showspouse'] = "Sýna maka (sýnir alla ef um fleiri en einn er að ræða)";
		$text['submitquery'] = "Sækja skýrslu";
		$text['birthplace'] = "Fæðingarstaður";
		$text['deathplace'] = "Dánarstaður";
		$text['birthdatetr'] = "Fæðingarár";
		$text['deathdatetr'] = "Dánarár";
		$text['plusminus2'] = "+/- 2 árum frá";
		$text['resetall'] = "Hreinsa öll gildi";
		$text['showdeath'] = "Sýna upplýsingar um andlát/jarðsetningu";
		$text['altbirthplace'] = "Skírnarstaður";
		$text['altbirthdatetr'] = "Skírnarár";
		$text['burialplace'] = "Nafn kirkjugarðs";
		$text['burialdatetr'] = "Jarðsetningarár";
		$text['event'] = "Atburðir";
		$text['day'] = "Dagur";
		$text['month'] = "Mánuður";
		$text['keyword'] = "Lykilorð (t.d, \"Abt\")";
		$text['explain'] = "Sláðu inn dagsetningu til að sjá atburði þann dag, eða skyldu reitina eftir auða til að sjá alla atburði.";
		$text['enterdate'] = "Vinsamlegast sláðu inn eða veldu að minnsta kosti eitt af eftirfarandi: Dagur, Mánuður, Ár, Lykil orð";
		$text['fullname'] = "Fullt nafn";
		$text['birthdate'] = "Fæðingardagur";
		$text['altbirthdate'] = "Skírnardagur";
		$text['marrdate'] = "Giftingardagur";
		$text['spouseid'] = "Nr. maka";
		$text['spousename'] = "Nafn maka";
		$text['deathdate'] = "Dánardagur";
		$text['burialdate'] = "Jarðsetningardagur";
		$text['changedate'] = "Dagsetning síðustu breytingar";
		$text['gedcom'] = "Tré";
		$text['baptdate'] = "Ferming (LDS)";
		$text['baptplace'] = "Fermingar staður (LDS)";
		$text['endldate'] = "Endowment Date (LDS)";
		$text['endlplace'] = "Endowment Place (LDS)";
		$text['ssealdate'] = "Seal Date S (LDS)";   //Sealed to spouse
		$text['ssealplace'] = "Seal Place S (LDS)";
		$text['psealdate'] = "Seal Date P (LDS)";   //Sealed to parents
		$text['psealplace'] = "Seal Place P (LDS)";
		$text['marrplace'] = "Hjónavígslustaður";
		$text['spousesurname'] = "Eftirnafn maka";
		$text['spousemore'] = "Ef þú slærð in eftirnafn maka verður þú að minnsta kosti að slá inn í einn annan reit fyrir viðkomandi.";
		$text['plusminus5'] = "+/- 5 árum frá";
		$text['exists'] = "er þegar til";
		$text['dnexist'] = "er ekki til";
		$text['divdate'] = "Dagsetning skilnaðar";
		$text['divplace'] = "Skilnaðar staður";
		$text['otherevents'] = "Aðrir atburðir";
		$text['numresults'] = "Niðurstöður á hverri síðu";
		$text['mysphoto'] = "Myndir sem vantar frekari upplýsingar um";
		$text['mysperson'] = "Einstaklingar sem vantar frekari upplýsingar um";
		$text['joinor'] = "'Skráðu þig með valkostur eða 'ekki er hægt að nota með Eftirnafn maka";
		$text['tellus'] = "Segðu okkur það sem þú veist";
		$text['moreinfo'] = "frekari upplýsingar:";
		//added in 8.0.0
		$text['marrdatetr'] = "Giftingarár";
		$text['divdatetr'] = "Skilnaðarár";
		$text['mothername'] = "Nafn móður";
		$text['fathername'] = "Nafn föðurs";
		$text['filter'] = "Sía";
		$text['notliving'] = "Ekki á lífi";
		$text['nodayevents'] = "Atburðir þessa mánaðar, sem ekki eru tengdir við tiltekinn dag:";
		//added in 9.0.0
		$text['csv'] = "Comma-delimited CSV file";
		//added in 10.0.0
		$text['confdate'] = "Fermingardagur (LDS)";
		$text['confplace'] = "Fermingarstaður (LDS)";
		$text['initdate'] = "Initiatory Date (LDS)";
		$text['initplace'] = "Initiatory Place (LDS)";
		//added in 11.0.0
		$text['marrtype'] = "Tegund hjónabands";
		$text['searchfor'] = "Leita að";
		$text['searchnote'] = "Ath: Þessi síða notar Google leitarvélina til að leita í öllum gögnum sem eru skráð á þessari síðu.  Fjöldi niðurstaða sem fram koma við leit, er háður því hversu vel Google hefur skráð síðuna.";
		break;

	//showlog.php
	case "showlog":
		$text['logfilefor'] = "loggar fyrir";
		$text['mostrecentactions'] = "Síðustu aðgerðir";
		$text['autorefresh'] = "Sjálfvirk endurnýjun (á 30 sekúndu fresti)";
		$text['refreshoff'] = "Sökkva á sjálfvirk endurnýjun";
		break;

	case "headstones":
	case "showphoto":
		$text['cemeteriesheadstones'] = "Kirkjugarðar og legsteinar";
		$text['showallhsr'] = "Sýna yfirlit yfir alla";
		$text['in'] = "inn";
		$text['showmap'] = "Sýna kort";
		$text['headstonefor'] = "legsteinn fyrir";
		$text['photoof'] = "Ljósmynd af";
		$text['photoowner'] = "Eigandi frumrits";
		$text['nocemetery'] = "engin grafreitur";
		$text['iptc005'] = "Titill";
		$text['iptc020'] = "stuðn. Flokkar";
		$text['iptc040'] = "Sérstakar leiðbeningar";
		$text['iptc055'] = "Skapað dags";
		$text['iptc080'] = "Höfundur";
		$text['iptc085'] = "Staðsetning höfundar";
		$text['iptc090'] = "Borg";
		$text['iptc095'] = "Ríki";
		$text['iptc101'] = "Land";
		$text['iptc103'] = "OTR";
		$text['iptc105'] = "Fyrirsögn";
		$text['iptc110'] = "spretta";
		$text['iptc115'] = "Ljósmyndaspretta";
		$text['iptc116'] = "Höfundarréttur";
		$text['iptc120'] = "Mynd af";
		$text['iptc122'] = "mynda gerð af";
		$text['mapof'] = "kort af";
		$text['regphotos'] = "Nákvæmari upplýsingar";
		$text['gallery'] = "Sjá bara smámyndir";
		$text['cemphotos'] = "Myndir úr kirkjugarði";
		$text['photosize'] = "Stærð";
        $text['iptc010'] = "Priority";
		$text['filesize'] = "Skráarstærð";
		$text['seeloc'] = "sjá staðsetningu";
		$text['showall'] = "Sýna allr";
		$text['editmedia'] = "Breyta margmiðlun";
		$text['viewitem'] = "Skoða þennan hlut";
		$text['editcem'] = "Breyta kirkjugarði";
		$text['numitems'] = "# Hlutir";
		$text['allalbums'] = "Öll myndaalbúm";
		$text['slidestop'] = "Stoppa myndasýningu";
		$text['slideresume'] = "Setja myndasýningu af stað";
		$text['slidesecs'] = "Fjöldi sekúndna fyrir hverja mynd:";
		$text['minussecs'] = "Mínus 0.5 sekúndur";
		$text['plussecs'] = "Plús 0.5 sekúndur";
		$text['nocountry'] = "Óþekkt land";
		$text['nostate'] = "Óþekkt fylki";
		$text['nocounty'] = "Óþekkt sýsla";
		$text['nocity'] = "Óþekkt borg";
		$text['nocemname'] = "Óþekktur kirkjugarður";
		$text['editalbum'] = "Breyta myndaalbúmi";
		$text['mediamaptext'] = "<strong>Ath:</strong> Færðu músarbendillinn þinn yfir mynd til að birta nöfn. Smelltu til að sjá síðu fyrir hvert nafn.";
		//added in 8.0.0
		$text['allburials'] = "Allar greftranir";
		$text['moreinfo'] = "meiri upplýsingar:";
		//added in 9.0.0
        $text['iptc025'] = "Lykilorð";
        $text['iptc092'] = "Sub-location";
		$text['iptc015'] = "Flokkur";
		$text['iptc065'] = "Originating Program";
		$text['iptc070'] = "Útgáfa forrits";
		//added in 13.0
		$text['toggletags'] = "Víxla merkjum";
		break;

	//surnames.php, surnames100.php, surnames-all.php, surnames-oneletter.php
	case "surnames":
	case "places":
		$text['surnamesstarting'] = "Sýna eftirnöfn sem byrja á";
		$text['showtop'] = "Sýna fyrstu";
		$text['showallsurnames'] = "Sýna öll eftirnöfn";
		$text['sortedalpha'] = "í stafrófsröð";
		$text['byoccurrence'] = "eftir fjölda";
		$text['firstchars'] = "Fyrstu stafina";
		$text['mainsurnamepage'] = "Upphafssíða fyrir eftirnöfn";
		$text['allsurnames'] = "Öll eftirnöfn";
		$text['showmatchingsurnames'] = "Smelltu á eftirnafn til að sjá fleiri.";
		$text['backtotop'] = "Aftur efst";
		$text['beginswith'] = "Byrjar á";
		$text['allbeginningwith'] = "Öll sem byrja á";
		$text['numoccurrences'] = "fjöldi í sviga";
		$text['placesstarting'] = "Sýna staði sem byrja á";
		$text['showmatchingplaces'] = "smelltu á nafn til að skoða.";
		$text['totalnames'] = "heildarfjöldi nafna";
		$text['showallplaces'] = "Sýna alla staði";
		$text['totalplaces'] = "heildarfjöldi staða";
		$text['mainplacepage'] = "Upphafssíða fyrir staði";
		$text['allplaces'] = "Allir staðir";
		$text['placescont'] = "Sýna alla staði sem innihalda";
		//changed in 8.0.0
		$text['top30'] = "xxx algengustu eftirnöfnin";
		$text['top30places'] = "xxx algengustu staðirnir";
		//added in 12.0.0
		$text['firstnamelist'] = "Yfirlit yfir fornöfn";
		$text['firstnamesstarting'] = "Sýna fornöfn sem byrja á";
		$text['showallfirstnames'] = "Sýna öll fornöfn";
		$text['mainfirstnamepage'] = "Yfirlit yfir öll skráð fornöfn";
		$text['allfirstnames'] = "Öll fornöfn";
		$text['showmatchingfirstnames'] = "Smelltu á nafn til að sjá niðurstöðurnar.";
		$text['allfirstbegwith'] = "Öll fornöfn sem byrja á";
		$text['top30first'] = "Fyrstu xxx fornöfnin";
		$text['allothers'] = "Öll önnur";
		$text['amongall'] = "(af öllum nöfnum)";
		$text['justtop'] = "Aðeins fyrstu xxx";
		break;

	//whatsnew.php
	case "whatsnew":
		$text['pastxdays'] = "(Síðustu xx dagana)";

		$text['photo'] = "Ljósmynd";
		$text['history'] = "Saga/Skal";
		$text['husbid'] = "Nr. eiginmanns";
		$text['husbname'] = "Nafn eiginmanns";
		$text['wifeid'] = "Nr. eiginkonu";
		//added in 11.0.0
		$text['wifename'] = "Mother's Name";
		break;

	//timeline.php, timeline2.php
	case "timeline":
		$text['text_delete'] = "Eyða";
		$text['addperson'] = "Bæta einstakling við";
		$text['nobirth'] = "Þessi einstaklingur er ekki með gildan fæðingardag og var því ekki hægt að bæta honum við";
		$text['event'] = "Viðburðir";
		$text['chartwidth'] = "Breidd";
		$text['timelineinstr'] = "bættu allt að fjórum fleirri einstaklingum með því að slá inn einstaklingsnúmerið þeirra hér fyrir neðan:";
		$text['togglelines'] = "Kveikja á línum";
		//changed in 9.0.0
		$text['noliving'] = "Þessi einstaklingur er merktur á lífi og var því ekki hægt að bæta honum við því þú ert ekki mé réttindi til þess";
		break;
		
	//browsetrees.php
	//login.php, newacctform.php, addnewacct.php
	case "trees":
	case "login":
		$text['browsealltrees'] = "Skoða öll tré";
		$text['treename'] = "Nafn trés";
		$text['owner'] = "Eigandi";
		$text['address'] = "Heimilisfang";
		$text['city'] = "Borg";
		$text['state'] = "Sýsla";
		$text['zip'] = "Póstnúmer";
		$text['country'] = "Land";
		$text['email'] = "Netfang";
		$text['phone'] = "Sími";
		$text['username'] = "Notendanafn";
		$text['password'] = "Lykilorð";
		$text['loginfailed'] = "Innskráning mistókst.";

		$text['regnewacct'] = "Notendaskráning";
		$text['realname'] = "Nafn";
		$text['phone'] = "Sími";
		$text['email'] = "Netfang";
		$text['address'] = "Heimilisfang";
		$text['acctcomments'] = "Skilaboð eða athugasemdir";
		$text['submit'] = "Senda";
		$text['leaveblank'] = "(hafðu autt ef óskað er eftir nýju tréi)";
		$text['required'] = "Verður að fylla út";
		$text['enterpassword'] = "Sláðu inn lykilorð.";
		$text['enterusername'] = "Sláðu inn notendanafn.";
		$text['failure'] = "Notendanafnið sem þú valdir er upptekið.  Farðu til baka til að velja þér nýtt notendanafn.";
		$text['success'] = "Takk fyrir. Skráningin þín hefur verið móttekin. þú verður látin vita þegar aðgangur þinn er orðin virkur eða meiri upplýsingar vantar.";
		$text['emailsubject'] = "Óskað hefur verið eftir aðgang að ættfræðisíðunni";
		$text['website'] = "Heimasíða";
		$text['nologin'] = "Vantar þig notendanafn?";
		$text['loginsent'] = "Upplýsingar sendar";
		$text['loginnotsent'] = "Aðgangs upplýsingar ekki sendar";
		$text['enterrealname'] = "Sláðu inn nafnið þitt.";
		$text['rempass'] = "Vera alltaf skráður á þessari tölvu";
		$text['morestats'] = "Meiri tölfræði";
		$text['accmail'] = "<strong>ATH:</strong> Gangtu úr skugga um að ekki þetta lén sé ekki á lista yfir lén sem lokað er á póst frá.";
		$text['newpassword'] = "Nýtt lykilorð";
		$text['resetpass'] = "Breyta lykilorði";
		$text['nousers'] = "Þetta form er ekki hægt að nota fyrr en einn notandi er til. Ef þú ert eigandi þessara síðu, farðu í admin/notendur til að búa til kerfistjóra aðgang.";
		$text['noregs'] = "Því miður, er ekki tekið á móti skráningum núna. Vinsamlegast hafðu <a href=\"suggest.php\">samband </a> beint ef þú hefur athugasemdir eða spurning er varðar eitthvað á þessari síðu.";
		$text['emailmsg'] = "Það hefur borist þér póstur um aðgang að nyðjatals síðunni. vinsamlegast skráðu þig inn á kerfis hluta síðunar og gefðu notenda réttindi til að taka þátt í að viðhalda síðunni. Ef þú notandi er í lagi vinsamlegast láttu hann vita með því að svara póstinum hanns.";
		$text['accactive'] = "Aðgangurinn hefur verið virkjaður en notandinn hefur engin sérstök réttindi fyrr en þú gefur honum þau.";
		$text['accinactive'] = "Farðu í Admin / Notandi / yfirlit til að fá aðgang að aðgangs stillingum. Aðgangurinn verður áfram óvirkur þar til þú breytir og vistar skrána að minnsta kosti einu sinni..";
		$text['pwdagain'] = "Lykilorð aftur";
		$text['enterpassword2'] = "Vinsamlegast skráðu inn lykilorðið þitt aftur.";
		$text['pwdsmatch'] = "Lykilorð þín eru ekki samstæð. Vinsamlegast sláðu inn sama aðgangsorðið í hvern reit.";
		$text['acksubject'] = "Þakka þér fyrir að skrá þig"; //for a new user account
		$text['ackmessage'] = "Beiðni þín um notanda hefur verið móttekin. Aðgangurinn þinn mun vera óvirkur þar til hann hefur verið skoðaður af  stjórnanda. Þér verður tilkynnt með tölvupósti þegar tenging þín er tilbúin til notkunar.";
		//added in 12.0.0
		$text['switch'] = "Víxla";
		//added in 14.0
		$text['newpassword2'] = "New password again";
		$text['resetsuccess'] = "Success: Password has been reset";
		$text['resetfail'] = "Failure: Password not reset";
		$text['failreason0'] = " (unknown database error)";
		$text['failreason2'] = " (you don't have permission to change your password)";
		$text['failreason3'] = " (passwords did not match)";
		break;

	//added in 10.0.0
	case "branches":
		$text['browseallbranches'] = "Skoða allar greinar";
		break;

	//statistics.php
	case "stats":
		$text['quantity'] = "Fjöldi";
		$text['totindividuals'] = "Fjöldi einstaklinga";
		$text['totmales'] = "Fjöldi manna";
		$text['totfemales'] = "Fjöldi kvenna";
		$text['totunknown'] = "Fjöldi einstaklinga þar sem kyn er ekki þekkt";
		$text['totliving'] = "Fjöldi lifandi einstaklinga";
		$text['totfamilies'] = "Fjöldi fjölskyldna";
		$text['totuniquesn'] = "Fjöldi eftirnafn";
		//$text['totphotos'] = "Total Photos";
		//$text['totdocs'] = "Total Histories &amp; Documents";
		//$text['totheadstones'] = "Total Headstones";
		$text['totsources'] = "Fjöldi heimilda";
		$text['avglifespan'] = "Meðal ævilengd";
		$text['earliestbirth'] = "Fyrsta fæðing";
		$text['longestlived'] = "Hæsti aldur";
		$text['days'] = "dagar";
		$text['age'] = "Aldur";
		$text['agedisclaimer'] = "Aldurs-tengdir útreikningar eru byggðir á einstaklingum með skráðar dagsetningar vegna fjölda óskráðra dagsetninga er þetta ekki alveg 100 prósent nákvæmt.";
		$text['treedetail'] = "Meiri upplýsingar um þetta tré";
		$text['total'] = "Samtals";
		//added in 12.0
		$text['totdeceased'] = "Total Deceased";
		//added in 14.0
		$text['totalsourcecitations'] = "Total Source Citations";
		break;

	case "notes":
		$text['browseallnotes'] = "Flétta í öllum athugasemdum";
		break;

	case "help":
		$text['menuhelp'] = "Valmynd";
		break;

	case "install":
		$text['perms'] = "aðgangsheimildir hafa verið settar.";
		$text['noperms'] = "aðgangsheimildir var ekki hægt að setja á þessar skrár:";
		$text['manual'] = "Vinsamlegast settu þá inn handvirkt.";
		$text['folder'] = "Mappa";
		$text['created'] = "hefur verið útbúinn";
		$text['nocreate'] = "gat ekki verið útbúinn. Vinsamlegast gerðu það handvirkt.";
		$text['infosaved'] = "Upplýsingar vistaðar, tenging staðfest!";
		$text['tablescr'] = "Töflur hafa verið búnar til!";
		$text['notables'] = "Eftirfarandi töflur gátu ekki verið útbúnar:";
		$text['nocomm'] = "TNG nær ekki sambandi við gagnagrunn. Engar töflur voru útbúnar.";
		$text['newdb'] = "Upplýsingar vistuð, tengingu staðfest, nýr gagnagrunnur til:";
		$text['noattach'] = "Upplýsingar vistaðar. Tengsl mynduð og gagnasafn skapað, en TNG getur ekki tengst.";
		$text['nodb'] = "Upplýsingar vistaðar. Tengsl gerð, en gagnagrunnur er ekki til og ekki hægt að skapa hér. Vinsamlegast staðfestu að gagnagrunns nafn sé rétt, eða nota stjórnborðið til að stofna hann..";
		$text['noconn'] = "Upplýsingar vistuð en tengingin tókst ekki. Einn eða fleiri af eftirfarandi er rangt:";
		$text['exists'] = "þegar til";
		$text['noop'] = "Ekkert var gert.";
		//added in 8.0.0
		$text['nouser'] = "Notandi var ekki búinn til. Notendanafn er líklega þegar til.";
		$text['notree'] = "Tré var ekki búið til. tré er líklega til.";
		$text['infosaved2'] = "Upplýsingar vistaðar";
		$text['renamedto'] = "endurskýrt í";
		$text['norename'] = "gat ekki verið endurskýrt";
		//changed in 13.0.0
		$text['loginfirst'] = "Notendagögn finnast fyrir í kerfinu. Til að halda áfram verður þú fyrst að skrá þig inn eða að eyða öllum skráningum í notendatöflu.";
		break;

	case "imgviewer":
		$text['magmode'] = "Stækka";
		$text['panmode'] = "Pan Mode";
		$text['pan'] = "Smelltu til að flytja innan myndar";
		$text['fitwidth'] = "passa breidd";
		$text['fitheight'] = "passa hæð";
		$text['newwin'] = "nýjan glugga";
		$text['opennw'] = "nýja mynd í nýjum glugga";
		$text['magnifyreg'] = "Smelltu til að stækka hluta af myndinni";
		$text['imgctrls'] = "Virkja myndstjórnun";
		$text['vwrctrls'] = "Virkja mynd skoðara stjórnum Image Viewer Controls";
		$text['vwrclose'] = "loka mynda skoðara";
		break;

	case "dna":
		$text['test_date'] = "Prófdagsetning";
		$text['links'] = "Relevant links";
		$text['testid'] = "Auðkennisnr. prófs";
		//added in 12.0.0
		$text['mode_values'] = "Mode Values";
		$text['compareselected'] = "Bera saman valið";
		$text['dnatestscompare'] = "Bera saman Y-DNA próf";
		$text['keep_name_private'] = "Keep Name Private";
		$text['browsealltests'] = "Browse All Tests";
		$text['all_dna_tests'] = "Öll DNA próf";
		$text['fastmutating'] = "Fast&nbsp;Mutating";
		$text['alltypes'] = "Allar tegundir";
		$text['allgroups'] = "Allir hópar";
		$text['Ydna_LITbox_info'] = "Test(s) linked to this person were not necessarily taken by this person.<br />The 'Haplogroup' column displays data in red if the result is 'Predicted' or green if the test is 'Confirmed'";
		//added in 12.1.0
		$text['dnatestscompare_mtdna'] = "Compare selected mtDNA Tests";
		$text['dnatestscompare_atdna'] = "Compare selected atDNA Tests";
		$text['chromosome'] = "Chr";
		$text['centiMorgans'] = "cM";
		$text['snps'] = "SNPs";
		$text['y_haplogroup'] = "Y-DNA";
		$text['mt_haplogroup'] = "mtDNA";
		$text['sequence'] = "Ref";
		$text['extra_mutations'] = "Extra Mutations";
		$text['mrca'] = "MRC forfaðir";
		$text['ydna_test'] = "Y-DNA Tests";
		$text['mtdna_test'] = "mtDNA (Mitochondrial) Tests";
		$text['atdna_test'] = "atDNA (autosomal) Tests";
		$text['segment_start'] = "Byrjun";
		$text['segment_end'] = "Endir";
		$text['suggested_relationship'] = "Suggested";
		$text['actual_relationship'] = "Actual";
		$text['12markers'] = "Markers 1-12";
		$text['25markers'] = "Markers 13-25";
		$text['37markers'] = "Markers 26-37";
		$text['67markers'] = "Markers 38-67";
		$text['111markers'] = "Markers 68-111";
		//added in 13.1
		$text['comparemore'] = "At least two tests must be selected to compare.";
		break;
}

//common
$text['matches'] = "Leitarniðurstöður:";
$text['description'] = "Lýsing";
$text['notes'] = "Athugasemdir";
$text['status'] = "Staða";
$text['newsearch'] = "Ný leit";
$text['pedigree'] = "Niðjatal";
$text['seephoto'] = "Sjá mynd";
$text['andlocation'] = "& staðsetning";
$text['accessedby'] = "Skoðað af";
$text['children'] = "Börn";  //from getperson
$text['tree'] = "Tré";
$text['alltrees'] = "Öll tré";
$text['nosurname'] = "[Eftirnafn vantar]";
$text['thumb'] = "Smámynd";  //as in Thumbnail
$text['people'] = "Fólk";
$text['title'] = "Titill";  //from getperson
$text['suffix'] = "Fornafn";  //from getperson
$text['nickname'] = "Gælunafn";  //from getperson
$text['lastmodified'] = "Síðast Breytt";  //from getperson
$text['married'] = "Gift(ur)";  //from getperson
//$text['photos'] = "Photos";
$text['name'] = "Fornafn"; //from showmap
$text['lastfirst'] = "Eftirnafn, fornafn";  //from search
$text['bornchr'] = "Fædd(ur)/Skírð(ur)";  //from search
$text['individuals'] = "Einstaklingar";  //from whats new
$text['families'] = "Fjölskyldur";
$text['personid'] = "Nr. einstaklings";
$text['sources'] = "Heimildir";  //from getperson (next several)
$text['unknown'] = "Ekki skírð(ur)";
$text['father'] = "Faðir";
$text['mother'] = "Móðir";
$text['christened'] = "Skírð(ur)";
$text['died'] = "Andlát";
$text['buried'] = "Jarðsetning";
$text['spouse'] = "Maki";  //from search
$text['parents'] = "Foreldrar";  //from pedigree
$text['text'] = "Texti";  //from sources
$text['language'] = "Tungumál";  //from languages
$text['descendchart'] = "Afkomendur";
$text['extractgedcom'] = "GEDCOM";
$text['indinfo'] = "Einstaklingur";
$text['edit'] = "Breyta";
$text['date'] = "Dags";
$text['login'] = "Innskráning";
$text['logout'] = "Útskráning";
$text['groupsheet'] = "Hóp Skrá";
$text['text_and'] = "og";
$text['generation'] = "Kynslóð";
$text['filename'] = "Skráarnafn";
$text['id'] = "ID";
$text['search'] = "Leita";
$text['user'] = "Notandi";
$text['firstname'] = "Fornafn";
$text['lastname'] = "Eftirnafn";
$text['searchresults'] = "Leitarniðurstöður";
$text['diedburied'] = "Andlát/Jarðsetning";
$text['homepage'] = "Upphafssíða";
$text['find'] = "Finna...";
$text['relationship'] = "Skyldleiki";		//in German, Verwandtschaft
$text['relationship2'] = "Relationship"; //different in some languages, at least in German (Beziehung)
$text['timeline'] = "Tímalína";
$text['yesabbr'] = "Já";               //abbreviation for 'yes'
$text['divorced'] = "Skilin";
$text['indlinked'] = "Tengist";
$text['branch'] = "Grein";
$text['moreind'] = "Fleiri einstaklingar";
$text['morefam'] = "Fleiri fjölskyldur";
$text['surnamelist'] = "Listi yfir eftirnöfn";
$text['generations'] = "Kynslóðir";
$text['refresh'] = "Endurnýja";
$text['whatsnew'] = "Hvað er nýtt";
$text['reports'] = "Skýrslur";
$text['placelist'] = "Listi yfir staði";
$text['baptizedlds'] = "Skírður (LDS)";
$text['endowedlds'] = "Fermdur (LDS)";
$text['sealedplds'] = "Sealed P (LDS)";
$text['sealedslds'] = "Sealed S (LDS)";
$text['ancestors'] = "Forfeður";
$text['descendants'] = "Afkomendur";
//$text['sex'] = "Sex";
$text['lastimportdate'] = "GEDCOM skýrsla síðast innflutt";
$text['type'] = "týpa";
$text['savechanges'] = "Breytingar vistaðar";
$text['familyid'] = "Nr. fjölskyldu";
$text['headstone'] = "Legsteinar";
$text['historiesdocs'] = "Saga &<br>Skjöl";
$text['anonymous'] = "ónefndur";
$text['places'] = "Staðir";
$text['anniversaries'] = "Dagsetningar og merkisatburðir";
$text['administration'] = "Vefstjórn";
$text['help'] = "Hjálp";
//$text['documents'] = "Documents";
$text['year'] = "Ár";
$text['all'] = "Allt";
$text['address'] = "Stinga uppá";
$text['suggest'] = "Suggest";
$text['editevent'] = "Stinga uppá að breyta þessum atburð";
$text['morelinks'] = "Fleiri tenglar";
$text['faminfo'] = "Upplýsingar um fjölskyldu";
$text['persinfo'] = "Upplýsingar um einstakling";
$text['srcinfo'] = "uppsprettu upplýsingar";
$text['fact'] = "Staðreynd";
$text['goto'] = "Velja síðu";
$text['tngprint'] = "Prenta";
$text['databasestatistics'] = "Tölfræði"; //needed to be shorter to fit on menu
$text['child'] = "Barn";  //from familygroup
$text['repoinfo'] = "Upplýsingar um heimildasöfn";
$text['tng_reset'] = "Hreinsa";
$text['noresults'] = "Engar niðurstöður fundust";
$text['allmedia'] = "Öll margmiðlun";
$text['repositories'] = "Heimildasöfn";
$text['albums'] = "Myndaalbúm";
$text['cemeteries'] = "Kirkjugarðar";
$text['surnames'] = "Eftirnöfn";
$text['dates'] = "Dagsetningar";
$text['link'] = "Tengill";
$text['media'] = "Margmiðlun";
$text['gender'] = "Kyn";
$text['latitude'] = "Breiddargráða";
$text['longitude'] = "Lengdargráða";
$text['bookmarks'] = "Bókamerki";
$text['bookmark'] = "Bæta við bókamerki";
$text['mngbookmarks'] = "Fara á bókarmerki";
$text['bookmarked'] = "Bókamerki bætt við";
$text['remove'] = "Fjarlægja";
$text['find_menu'] = "Finna";
$text['info'] = "Upplýsingar"; //this needs to be a very short abbreviation
$text['cemetery'] = "Kirkjugarður";
$text['gmapevent'] = "Kort yfir atburði";
$text['gevents'] = "Atburðir";
$text['googleearthlink'] = "Tengill á Google Earth";
$text['googlemaplink'] = "Tengill á Google Maps";
$text['gmaplegend'] = "Skýringar á merkingum";
$text['unmarked'] = "Ómerkt";
$text['located'] = "Staðsettur";
$text['albclicksee'] = "Smelltu til að sjá alla hluti í þessu myndaalbúmi";
$text['notyetlocated'] = "ekki fundinn enn";
$text['cremated'] = "Brenndur";
$text['missing'] = "Vantar";
$text['pdfgen'] = "Búa til PDF skjal";
$text['blank'] = "Tómt kort";
$text['fonts'] = "Letur";
$text['header'] = "Haus";
$text['data'] = "Upplýsingar";
$text['pgsetup'] = "Síðu uppsetning";
$text['pgsize'] = "Síðu Stærð";
$text['orient'] = "Snúningur"; //for a page
$text['portrait'] = "Portrait";
$text['landscape'] = "Landscape";
$text['tmargin'] = "Efri spássía";
$text['bmargin'] = "Neðri Spássía";
$text['lmargin'] = "Vinstri spássía";
$text['rmargin'] = "Hægri spássía";
$text['createch'] = "Búa til kort";
$text['prefix'] = "Forskeyti";
$text['mostwanted'] = "Eftirlýsingar";
$text['latupdates'] = "Síðustu uppfærslur";
$text['featphoto'] = "Myndir af handahófi";
$text['news'] = "Fréttir";
$text['ourhist'] = "Fjölskyldu saga okkar";
$text['ourhistanc'] = "Fjölskyldusaga okkar og afkomendur";
$text['ourpages'] = "Fjölskyldu nyðjatal";
$text['pwrdby'] = "Þessi síða er hönnuð af";
$text['writby'] = "sem er búið til af";
$text['searchtngnet'] = "Leita á TNG Síðunum (GENDEX)";
$text['viewphotos'] = "Skoða allar myndir";
$text['anon'] = "Þú ert ekki skráður undir nafni";
$text['whichbranch'] = "Hvaða grein kemur þú frá?";
$text['featarts'] = "Grein af handófi";
$text['maintby'] = "Umsjón síðu";
$text['createdon'] = "Búinn til af";
$text['reliability'] = "Áreiðanleiki";
$text['labels'] = "Merkingar";
$text['inclsrcs'] = "Hafa Heimildir";
$text['cont'] = "(áframh.)"; //abbreviation for continued
$text['mnuheader'] = "Ættfræðisíða";
$text['mnusearchfornames'] = "Leit";
$text['mnulastname'] = "Eftirnafn";
$text['mnufirstname'] = "Fornafn";
$text['mnusearch'] = "Leita";
$text['mnureset'] = "Byrja upp á nýtt";
$text['mnulogon'] = "Innskrá";
$text['mnulogout'] = "Útskráning";
$text['mnufeatures'] = "Aðrir kostir";
$text['mnuregister'] = "Skráning fyrir notenda aðgang";
$text['mnuadvancedsearch'] = "Nákvæmari leit";
$text['mnulastnames'] = "Eftirnöfn";
$text['mnustatistics'] = "Tölfræði";
$text['mnuphotos'] = "Myndir";
$text['mnuhistories'] = "Sögur";
$text['mnumyancestors'] = "Photos &amp; Histories for Ancestors of [Person]";
$text['mnucemeteries'] = "Kirkjugarðar";
$text['mnutombstones'] = "Legsteinar";
$text['mnureports'] = "Skýrslur";
$text['mnusources'] = "Heimildir";
$text['mnuwhatsnew'] = "Hvað er nýtt";
$text['mnushowlog'] = "Loggar";
$text['mnulanguage'] = "Breyta tungumáli";
$text['mnuadmin'] = "Vefstjórn";
$text['welcome'] = "Velkomin(n)";
$text['contactus'] = "Hafðu samband";
//changed in 8.0.0
$text['born'] = "Fædd(ur)";
$text['searchnames'] = "Leita í nöfnum";
//added in 8.0.0
$text['editperson'] = "Breytingar á einstakling";
$text['loadmap'] = "hlaða korti";
$text['birth'] = "Fæðing";
$text['wasborn'] = "Fæddist þann";
$text['startnum'] = "Upphafstala";
$text['searching'] = "leita";
//moved here in 8.0.0
$text['location'] = "Staðsetning";
$text['association'] = "Tenging";
$text['collapse'] = "Fella allt saman";
$text['expand'] = " Þenja allt út";
$text['plot'] = "Plot";
$text['searchfams'] = "Leita í fjölskyldum";
//added in 8.0.2
$text['wasmarried'] = "giftist";
$text['anddied'] = "og lést þann";
//added in 9.0.0
$text['share'] = "Deila";
$text['hide'] = "Fela";
$text['disabled'] = "Notendareikningur þinn hefur verið gerður óvirkur.  Vinsamlega hafðu samband við stjórnanda heimasíðunnar til að fá frekari upplýsingar.";
$text['contactus_long'] = "Ef þú hefur einhverjar spurningar eða athugasemdir um þessa heimasíðu, vinsamlega <span class=\"emphasis\"><a href=\"suggest.php\">hafið samband</a></span>. Við hlökkum til að heyra frá þér.";
$text['features'] = "Features";
$text['resources'] = "Resources";
$text['latestnews'] = "Nýjustu fréttir";
$text['trees'] = "Tré";
$text['wasburied'] = "var jarðsett(ur) þann";
//moved here in 9.0.0
$text['emailagain'] = "Netfang aftur";
$text['enteremail2'] = "Vinsamlegast sláðu inn netfangið þitt aftur.";
$text['emailsmatch'] = "Vinsamlegast sláðu inn sama netfang í hvern reit.";
$text['getdirections'] = "Smelltu hér til að fá upplýsingar um það hvernig þú kemst til:";
$text['calendar'] = "Dagatal";
//changed in 9.0.0
$text['directionsto'] = " til ";
$text['slidestart'] = "Hefja myndasýningu";
$text['livingnote'] = "Lifandi einstaklingur - Nánari upplýsingar faldar";
$text['livingphoto'] = "Að minnstakosti einn einstaklingur á þessari mynd er lifandi - upplýsingar um mynd ekki gefnar upp.";
$text['waschristened'] = "var skírð(ur)";
//added in 10.0.0
$text['branches'] = "Greinar";
$text['detail'] = "Detail";
$text['moredetail'] = "More detail";
$text['lessdetail'] = "Less detail";
$text['conflds'] = "Confirmed (LDS)";
$text['initlds'] = "Initiatory (LDS)";
$text['wascremated'] = "var brennd(ur)";
//moved here in 11.0.0
$text['text_for'] = "fyrir";
//added in 11.0.0
$text['searchsite'] = "Leita á ";
$text['searchsitemenu'] = "Leita í öllum gögnum";
$text['kmlfile'] = "Sæktu .kml skýrslu til að sýna þessa staðsetningu í Google Earth";
$text['download'] = "Smelltu til að sækja";
$text['more'] = "Meira";
$text['heatmap'] = "Hitakort";
$text['refreshmap'] = "Endurnýja kort";
$text['remnums'] = "Clear Numbers and Pins";
$text['photoshistories'] = "Myndir &amp; Sögur";
$text['familychart'] = "Family Chart";
//added in 12.0.0
$text['firstnames'] = "Fornöfn";
//moved here in 12.0.0
$text['dna_test'] = "DNA próf";
$text['test_type'] = "Próftegund";
$text['test_info'] = "Prófupplýsingar";
$text['takenby'] = "Tekið af";
$text['haplogroup'] = "Haplogroup";
$text['hvr1'] = "HVR1";
$text['hvr2'] = "HVR2";
$text['relevant_links'] = "Relevant links";
$text['nofirstname'] = "[ekkert fornafn]";
//added in 12.0.1
$text['cookieuse'] = "Ath.: Athugið þessi síða notar fótspor/vafrakökur.";
$text['dataprotect'] = "Persónuverndarstefna";
$text['viewpolicy'] = "Skoða persónuverndarstefnu";
$text['understand'] = "I understand";
$text['consent'] = "I give my consent for this site to store the personal information collected here. I understand that I may ask the site owner to remove this information at any time.";
$text['consentreq'] = "Please give your consent for this site to store personal information.";

//added in 12.1.0
$text['testsarelinked'] = "DNA tests are associated with";
$text['testislinked'] = "DNA test is associated with";

//added in 12.2
$text['quicklinks'] = "Flýtileiðir";
$text['yourname'] = "Nafn þitt";
$text['youremail'] = "Netfangið þitt";
$text['liketoadd'] = "Þær uppl. sem þú vilt bæta við";
$text['webmastermsg'] = "Skilaboð vefstjóra";
$text['gallery'] = "Sjá gallerí";
$text['wasborn_male'] = "fæddist";         // same as $text['wasborn'] if no gender verb
$text['wasborn_female'] = "fæddist";      // same as $text['wasborn'] if no gender verb
$text['waschristened_male'] = "var skírður";         // same as $text['waschristened'] if no gender verb
$text['waschristened_female'] = "var skírð";         // same as $text['waschristened'] if no gender verb
$text['died_male'] = "dó";            // same as $text['anddied'] of no gender verb
$text['died_female'] = "dó";        // same as $text['anddied'] of no gender verb
$text['wasburied_male'] = "var jarðaður";            // same as $text['wasburied'] if no gender verb
$text['wasburied_female'] = "var jörðuð";            // same as $text['wasburied'] if no gender verb
$text['wascremated_male'] = "var brenndur";                     // same as $text['wascremated'] if no gender verb
$text['wascremated_female'] = "var brennd";      // same as $text['wascremated'] if no gender verb
$text['wasmarried_male'] = "giftist";       // same as $text['wasmarried'] if no gender verb
$text['wasmarried_female'] = "giftist";    // same as $text['wasmarried'] if no gender verb
$text['wasdivorced_male'] = "skildi";       // might be the same as $text['divorce'] but as a verb
$text['wasdivorced_female'] = "skildi";    // might be the same as $text['divorce'] but as a verb
$text['inplace'] = " í ";                                   // used as a preposition to the location
$text['onthisdate'] = " þann ";                    // when used with full date
$text['inthisyear'] = " á ";                             // when used with year only or month / year dates
$text['and'] = " og ";                                                     // used in conjunction with wasburied or was cremated

//moved here in 12.2.1
$text['dna_info_head'] = "DNA Test Info";
//added in 13.0
$text['visitor'] = "Gestur";

$text['popupnote2'] = " = Nýtt niðjatal";

//moved here in 14.0
$text['zoomin'] = "þysja inn";
$text['zoomout'] = "þysja út";
$text['scrollnote'] = "Skrunaðu eða dragðu til að sjá meira af myndinni.";
$text['general'] = "Almennt";

//changed in 14.0
$text['otherevents'] = "Aðrir atburðir";

//added in 14.0
$text['times'] = "x";
$text['connections'] = "Tengingar";
$text['continue'] = "Halda áfram";
$text['title2'] = "Titill"; //for media, sources, etc (not people)

@include_once(dirname(__FILE__) . "/alltext.php");
if(empty($alltextloaded)) getAllTextPath();
?>