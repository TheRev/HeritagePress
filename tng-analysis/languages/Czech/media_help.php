<?php
include("../../helplib.php");
echo help_header("Nápovìda: Média");
?>

<body class="helpbody">
<a name="top"></a>
<table width="100%" border="0" cellpadding="10" cellspacing="2" class="tblback normal">
<tr class="fieldnameback">
	<td class="tngshadow">
		<p style="float:right; text-align:right" class="smaller menu">
			<a href="https://tng.community" target="_blank" class="lightlink">TNG Forum</a> &nbsp; | &nbsp;
			<a href="https://tng.lythgoes.net/wiki" target="_blank" class="lightlink">TNG Wiki</a><br />
			<a href="more_help.php" class="lightlink">&laquo; Nápovìda: Více</a> &nbsp; | &nbsp;
			<a href="collections_help.php" class="lightlink">Nápovìda: Kolekce &raquo;</a>
		</p>
		<span class="largeheader">Nápovìda: Média</span>
		<p class="smaller menu">
			<a href="#search" class="lightlink">Hledat</a> &nbsp; | &nbsp;
			<a href="#add" class="lightlink">Pøidat</a> &nbsp; | &nbsp;
			<a href="#edit" class="lightlink">Upravit</a> &nbsp; | &nbsp;
			<a href="#delete" class="lightlink">Vymazat</a> &nbsp; | &nbsp;
			<a href="#convert" class="lightlink">Pøevést</a> &nbsp; | &nbsp;
			<a href="#album" class="lightlink">Pøidat do alba</a> &nbsp; | &nbsp;
			<a href="#sort" class="lightlink">Seøadit</a> &nbsp; | &nbsp;
			<a href="#thumbs" class="lightlink">Náhledy</a> &nbsp; | &nbsp;
			<a href="#import" class="lightlink">Import</a> &nbsp; | &nbsp;
 			<a href="#upload" class="lightlink">Nahrát</a>
		</p>
	</td>
</tr>
<tr class="databack">
	<td class="tngshadow">
		<div id="google_translate_element" style="float:right"></div><script type="text/javascript">
		function googleTranslateElementInit() {
		  new google.translate.TranslateElement({pageLanguage: 'en'}, 'google_translate_element');
		}
		</script><script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

		<a name="search"><p class="subheadbold">Hledat</p></a>
    <p>Nalezení existujících médií vyhledáním celého nebo èásti <strong>ID èísla média, titulu, popisu, umístìní</strong> nebo
		<strong>základního textu</strong>. Pro dal¹í zú¾ení va¹eho výbìru pou¾ijte dal¹í dostupné mo¾nosti.
		Vyhledávání bez vybraných voleb a bez zapsaných hodnot ve výbìrových polí povede k výbìru v¹ech médií z va¹í databáze. Vyhledávací volby obsahují:</p>

		<span class="optionhead">Strom</span>
		<p>Omezí výsledek na média spojená pouze s vybraným stromem.</p>

		<span class="optionhead">Kolekce</span>
		<p>Omezí výsledek na média vybraného typu kolekce. Chcete-li pøidat novou kolekci, kliknìte na tlaèítko "Pøidat kolekci", a v zobrazeném oknì vyplòte formuláø.
		Pro va¹i novou kolekci musíte vytvoøit slo¾ku a musíte vytvoøit vlastní ikonu (nebo pou¾ít nìjakou stávající). Pole "Stejné nastavení jako"
		vám umo¾ní oznaèit, ze které ze stávajících kolekcí si nová kolekce vezme nastavení.</p>

		<span class="optionhead">Pøípona souboru</span>
		<p>Pøed kliknutím na tlaèítko Hledat zapi¹te pøíponu souboru (napø. "jpg" nebo "gif") pro omezení výsledku na média
		s názvem souboru, který obsahuje tuto pøíponu.</p>

		<span class="optionhead">Pouze nepøipojené</span>
		<p>Pøed kliknutím na tlaèítko Hledat za¹krtnìte toto políèko pro omezení výsledku na média, která nejsou pøipojena k ¾ádné osobì,
		rodinì, pramenu, úlo¾i¹ti pramenù nebo místu.</p>

  		<span class="optionhead">Stav</span>
		<p><strong>(pouze Náhrobky)</strong> Pøed kliknutím na tlaèítko Hledat vyberte ze seznamu stav pro zobrazení v¹ech záznamù náhrobkù se stejným stavem.</p>

		<span class="optionhead">Høbitov</span>
		<p>Pøed kliknutím na tlaèítko Hledat vyberte ze seznamu høbitov pro zobrazení v¹ech záznamù náhrobkù spojených s vybraným høbitovem.</p>

    <p>Vyhledávací kritéria, která zadáte na této stránce, budou uchována, dokud nekliknete na tlaèítko <strong>Obnovit</strong>, které znovu obnoví v¹echny výchozí hodnoty.</p>

    <span class="optionhead">Akce</span>
		<p>Tlaèítko Akce vedle ka¾dého výsledku hledání vám umo¾ní upravit, vymazat nebo otestovat výsledek. Chcete-li najednou vymazat více osob, za¹krtnìte políèko ve sloupci
		<strong>Vybrat</strong> u ka¾dého záznamu, která má být odstranìn, a poté kliknìte na tlaèítko "Vymazat oznaèené" na zaèátku seznamu. Pro za¹krtnutí nebo vyèi¹tìní v¹ech výbìrových políèek najednou
    mù¾ete pou¾ít tlaèítka <strong>Vybrat v¹e</strong> nebo <strong>Vyèistit v¹e</strong>.</p>

	</td>
</tr>
<tr class="databack">
	<td class="tngshadow">

		<p style="float:right"><a href="#top">Nahoru</a></p>
		<a name="add"><p class="subheadbold">Pøidat nové médium</p></a>

		<p>Chcete-li pøidat nové médium, kliknìte na zálo¾ku <strong>Pøidat nové</strong> a poté vyplòte formuláø. Dal¹í informace jako obrázek mapy, informace o místì a
		odkazy na osoby, rodiny a dal¹í subjekty mù¾ete pøidat po ulo¾ení nebo zamknutí záznamu. Význam jednotlivých polí je následující:</p>

		<span class="optionhead">Kolekce</span>
		<p>Vyberte typ média, kterým je va¹e polo¾ka (napø. fotografie, dokumenty, náhrobky, vyprávìní, zvukový záznam nebo video). ®ádná z <span class="emphasis">kolekcí</span> médií není omezena typem souboru.</p>

		<span class="optionhead">Toto médium je z externího zdroje</span>
		<p>Toto políèko za¹krtnìte, pokud se obrázek nachází nìkde na internetu jinde ne¾ na va¹em serveru. Do pole oznaèeného "URL média" musíte zapsat
		úplnou webovou adresu (napø. <em>https://www.tentoweb.com/image.jpg</em>), a
		pokud chcete mít náhled tohoto obrázku, musíte pøidat vlastní (TNG jej nevytvoøí).</p>

		<span class="optionhead">Soubor s médiem</span>
		<p>Vyberte fyzický soubor (ze svého lokálního poèítaèe nebo z va¹ich webových stránek) pro tuto mediální polo¾ku.</p>

		<span class="optionhead">Soubor pro nahrání</span>
		<p>Pokud va¹e nová mediální polo¾ka je¹tì nebyla nahrána na va¹e webové stránky, kliknìte na tlaèítko "Procházet" a vyhledejte ji na va¹em disku.
		Je-li tato polo¾ka ji¾ na va¹ich stránkách, nechte toto pole prázdné.</p>

		<span class="optionhead">Název souboru na stránkách / Media URL</span>
		<p>Pokud jste ji¾ va¹i mediální polo¾ku nahráli na stránky, zapi¹te umístìní a název souboru va¹í polo¾ky tak, jak existuje ve slo¾ce odpovídající kolekce na va¹ich webových stránkách,
		nebo kliknìte na tlaèítko "Vybrat" a vyhledejte soubor ve slo¾ce pøíslu¹né kolekce. Pokud nahráváte va¹i mediální polo¾ku nyní
		pomocí pøedchozího pole, pou¾ijte toto pole pro zápis umístìní a názvu souboru a¾ po nahrání souboru. Pøedpokládané umístìní a
		název souboru bude pøedvyplnìno. Pokud jste oznaèili, ¾e toto médium pochází z externího zdroje, popis tohoto pole se zmìní na "URL média",
		a v tomto pøípadì byste mìli zapsat absolutní URL.</p>

		<p><strong>POZN.</strong>: Budete-li na stránky nahrávat nyní, adresáø, který jste zde oznaèili, musí existovat a musí mít nastaveno právo na zápis pro v¹echny. Pokud ne, pou¾ijte vá¹ FTP program
		nebo jiný online souborový správce, vytvoøte slo¾ku a dejte ji pøíslu¹ná oprávnìní (fungovat by mìlo 775, ale na nìkterých stránkách je po¾adováno 777). </p>

		<span class="optionhead">NEBO Základní text</span>
		<p>Místo nahrání fyzického souboru mù¾ete do tohoto pole zapsat nebo vlo¾it text nebo HTML kód.
		Pro formátování textu mù¾ete také pou¾ít ovládací prvky na horním okraji pole Základní text. Pøidr¾íte-li kursor my¹i nad ovládacími prvky, uvidíte jejich funkci.</p>

		<p><strong>POZN.:</strong> Pokud pou¾íváte HTML, <strong>nevkládejte</strong> HTML nebo BODY tagy.</p>

		<span class="optionhead">Soubor s náhledem obrázku</span>
		<p>Jako náhled (men¹í obrázek) této mediální polo¾ky mù¾ete vybrat existující fyzický soubor (ze svého lokálního poèítaèe nebo z va¹ich webových stránek)
		nebo pro vás tento náhled vytvoøí TNG. Pokud nevyberete ani jednu volbu, TNG pou¾ije výchozí soubor náhledu. <strong>Pozn.:</strong>
		Náhled by mìl mít ideálnì stranu o velikost 50 a¾ 100 pixelù. Vá¹ náhled <strong>NEMÙ®E</strong> být toto¾ný s originálním obrázkem! TNG se ozve, pokud se pokusíte pou¾ít
		pùvodní obrázek jako náhled. Pokud náhled ji¾ nemáte, TNG jej pro vás mù¾e vytvoøit, ale pouze, pokud je va¹e mediální polo¾ka platný obrázek JPG, GIF nebo PNG.
    TNG mù¾e také vytvoøit náhledy z nìkterých souborù PDF, ale stále mù¾e po vás po¾adovat, abyste pro jiné soubory (zejména star¹í soubory PDF) nahrali vlastní náhledy.</p>

		<span class="optionhead">Zadat obrázek/Vytvoøit z originálu</span>
		<p>Pokud vá¹ server podporuje knihovnu GD image, uvidíte zde mo¾nost zapsat vá¹ vlastní
		náhled nebo nechat jej TNG vytvoøit z originálu. Vyberete-li druhou mo¾nost, bude standardnì název nového souboru stejný jako název originálu, ale s pøedponou
		a/nebo pøíponou navíc. Tato pøedpona a pøípona, spolu s maximální vý¹kou a délkou náhledu, je nastavena v Základním nastavení. <strong>Pozn.:</strong> Vá¹
    náhled <strong>NEMÙ®E</strong> být toto¾ný s originálním obrázkem! TNG se ozve, pokud se pokusíte pou¾ít
		pùvodní obrázek jako náhled. Pokud náhled ji¾ nemáte, TNG jej pro vás mù¾e vytvoøit, ale pouze, pokud je va¹e mediální polo¾ka ve formátu JPG, GIF nebo PNG (v nìkterém pøípadì i PDF).
    PHP se mù¾e ozvat, pokud chcete vytvoøit náhled z pøíli¹ velkého obrázku (více ne¾ 1MB).</p>

		<span class="optionhead">Soubor pro nahrání</span>
		<p>Pøi po¾adavku na vytvoøení rodokmenu osoby jsou náhledy jednotlivých fotografií souvisejících s danou osobou zobrazeny na stejné stránce. Pokud obrázek náhledu
		va¹í mediální polo¾ky je¹tì nebyl na va¹e webové stránky nahrán, kliknìte na tlaèítko "Procházet" a vyhledejte náhled na va¹em disku.
		Do dal¹ího pole pak musíte zadat cílové umístìní a název souboru obrázku náhledu.
		Je-li tento náhled ji¾ na va¹ich stránkách, nechte toto pole prázdné.</p>

		<span class="optionhead">Název souboru na stránkách</span>
    <p>Pokud jste ji¾ vá¹ soubor náhledu nahráli na stránky, zapi¹te umístìní a název souboru va¹eho náhledu tak, jak existuje ve slo¾ce odpovídající kolekce na va¹ich webových stránkách
		(tip: náhledy mù¾ete ulo¾it v podslo¾ce, pokud chcete, aby byly uchovávány oddìlenì, nebo mají stejné názvy jako vìt¹í obrázky). Pokud neznáte pøesný název souboru,
    mù¾ete kliknout na tlaèítko "Vybrat" a vyhledejte soubor. Pokud nahráváte vá¹ soubor náhledu nyní pomocí pøedchozího pole, pou¾ijte toto pole pro zápis umístìní a
    názvu souboru a¾ po nahrání souboru. Pøedpokládané umístìní a	název souboru bude pøedvyplnìno.</p>

    <p><strong>POZN.</strong>: Budete-li na stránky nahrávat nyní, adresáø, který jste zde oznaèili, musí existovat a musí mít nastaveno právo na zápis pro v¹echny. Pokud ne, pou¾ijte vá¹ FTP program
		nebo jiný online souborový správce, vytvoøte slo¾ku a dejte ji pøíslu¹ná oprávnìní (fungovat by mìlo 775, ale na nìkterých stránkách je po¾adováno 777). </p>

		<span class="optionhead">Soubory ulo¾it ve: Slo¾ce multimédií / Slo¾ce kolekce</span>
		<p>Mù¾ete zvolit, zda má být tato mediální polo¾ka ulo¾ena ve slo¾ce odpovídající kolekci vybrané vý¹e (výchozí mo¾nost) nebo ji mù¾ete ulo¾it v obecné slo¾ce
		multimédií.</p>

		<span class="optionhead">Titul</span>
		<p>Titul by mìl být krátký &#151; pouze pár slov k identifikaci va¹í mediální polo¾ky. Bude pou¾it jako odkaz na stránce zobrazující va¹i polo¾ku.</p>

		<span class="optionhead">Popis</span>
		<p>Do tohoto pole vlo¾te více podrobností, vèetnì informace, kdo nebo co je zobrazeno nebo popsáno, apod. Toto pole bude
    doprovázet krátký popis (viz pøedchozí pole).</p>

		<span class="optionhead">©íøka, vý¹ka</span>
		<p><strong>(pouze video)</strong> Nìkteré pøehrávaèe videa (napø. Quicktime) po¾adují specifickou ¹íøku a vý¹ku videa. Nejsou-li tyto rozmìry specifikovány, mù¾e pak být video pøíli¹ oøíznuté
		a nìkteré èásti videa nemusí být viditelné. Proto doporuèujeme, abyste sem zapsali velikost va¹eho videa v pixelech. Pamatujte také na to,
		abyste poèítali s asi 16 pixely na ovladaèe videa (ovladaèe play/stop/volume, atd.).</p>

		<span class="optionhead">Majitel/Pramen, Datum poøízení</span>
		<p>Toto jsou nepovinná pole. Pokud tyto údaje znáte, zapi¹te je do pøíslu¹ných polí.</p>

		<span class="optionhead">Strom</span>
		<p>Chcete-li spojit toto médium s urèitým stromem, vyberte jej zde. Bude to mít vliv na u¾ivatele, kteøí mají právo pouze upravovat
		polo¾ky spojené s jejich pøidìleným stromem.</p>

		<span class="optionhead">Høbitov</span>
		<p>Chcete-li tuto mediální polo¾ku pøidru¾it ke høbitovu, vyberte høbitov z rozbalovacího seznamu. Obvykle se to pou¾ívá u náhrobkù k oznaèení, kde se náhrobek nachází. Nejprve musíte pøidat høbitov
    (v èásti Administrace/Høbitovy), pak bude viditelný v tomto poli.</p>

		<span class="optionhead">Pozemek</span>
		<p><strong>(pouze Náhrobky)</strong> Pozemek, kde se nachází náhrobek (nepovinné).</p>

		<span class="optionhead">Stav</span>
		<p><strong>(pouze Náhrobky)</strong> Z rozbalovacího seznamu vyberte slovo nebo frázi, která nejlépe popisuje stav fyzického náhrobku.</p>

		<span class="optionhead">V¾dy viditelné</span>
		<p>Toto políèko za¹krtnìte, pokud chcete, aby toto médium bylo u pøipojených osob zobrazeno v¾dy bez ohledu na u¾ivatelská oprávnìní nebo zda se jedná o osobu ¾ijící.</p>

		<span class="optionhead">Otevøít v novém oknì</span>
		<p>Toto políèko za¹krtnìte, pokud chcete, aby se polo¾ka po kliknutí na její odkaz otevøela v novém oknì.</p>

		<span class="optionhead">Neveøejné</span>
		<p>Za¹krtnutím bude tato polo¾ka skryta, pokud u¾ivatel nemá pøístup k neveøejným údajùm.</p>

		<span class="optionhead">Spojit toto médium pøímo s vybraným høbitovem</span>
		<p><strong>(pouze Náhrobky)</strong> Za¹krtnutím tohoto políèka spojíte tento obrázek náhrobku se samotným høbitovem. Pøi zobrazení stránky høbitova se v¹echny mediální polo¾ky
		spojené se høbitovem tímto zpùsobem zobrazí v horní èásti stránky.</p>

		<span class="optionhead">Ukázat mapu høbitova a médium, kdykoliv bude tato polo¾ka zobrazena</span>
		<p><strong>(pouze Náhrobky)</strong> Pokud má høbitov, na které se náhrobek nachází, pøilo¾enou mapu nebo fotografii, za¹krtnutím tohoto políèka se mapa nebo fotografie zobrazí kdykoli
		je zobrazen náhrobek.</p>

	</td>
</tr>
<tr class="databack">
	<td class="tngshadow">

		<p style="float:right"><a href="#top">Nahoru</a></p>
		<a name="edit"><p class="subheadbold">Upravit existující médium</p></a>
		<p>Chcete-li upravit existující médium, k nalezení polo¾ky pou¾ijte zálo¾ku <a href="#search">Hledat</a>, a poté kliknìte na ikonu Upravit vedle této polo¾ky.
		Význam polí, která nejsou na stránce "Pøidat nové médium", je následující:</p>

		<span class="optionhead">Odkazy na médium</span>
		<p>Toto médium mù¾ete pøipojit k osobám, rodinám, pramenùm, úlo¾i¹tím pramenù nebo místùm. Pro ka¾dý odkaz nejdøíve vyberte strom spojený se subjektem, se kterým chcete polo¾ku spojit.
		Dále vyberte typ odkazu (osoba, rodina, pramen, úlo¾i¹tì pramenù nebo místo) a na závìr ID èíslo nebo název (pouze u místa) subjektu, se kterým polo¾ku spojujete.
		Po vlo¾ení v¹ech tìchto údajù kliknìte na tlaèítko "Pøidat".</p>

		<p>Pokud neznáte ID èíslo nebo pøesný název místa, kliknutím na ikonu lupy je mù¾ete vyhledat. Objeví se okno, ve kterém mù¾ete hledat.
		Po nalezení po¾adovaného popisu subjektu kliknìte na odkaz "Pøidat" vlevo. Kliknout na "Pøidat" mù¾ete u více subjektù. Po ukonèení vytváøení
		odkazù kliknìte na odkaz "Zavøít okno".</p>

		<p><strong>Existující odkazy:</strong> Existující odkazy mù¾ete upravit nebo vymazat kliknutím na ikonu Upravit nebo Vymazat vedle tohoto odkazu. Úprava odkazu
		vám umo¾ní spojit odkaz s urèitou událostí a pøidìlit mu <strong>Alternativní titul</strong> a <strong>Alternativní popis</strong>. Pro ka¾dý odkaz mù¾ete
		kliknutím na pøíslu¹né políèko také zmìnit <strong>Výchozí fotografii</strong> nebo stav <strong>Zobrazit</strong>. Ní¾e jsou uvedeny dal¹í informace o tìchto vlastnostech.</p>

		<p>Kliknutím na odkaz "Seøadit" vedle jména se dostanete rychle na stránku, na které mù¾ete pøetøídit jednotlivé mediální polo¾ky tohoto subjektu. Tuté¾ vìc mù¾ete provést kliknutím
		na zálo¾ku Seøadit na horním okraji stránky Média, ale tento zpùsob je rychlej¹í.</p>

		<p><strong>VAROVÁNÍ</strong>: Odkazy na urèité události, které vytvoøíte v TNG, mohou být následným importem souboru GEDCOM pøepsány.</p>

		<span class="optionhead">Nastavit jako výchozí</span>
		<p>Za¹krtnutím tohoto políèka bude náhled tohoto média pou¾it ve schématu vývodu a v horní èásti dal¹ích stránek, které souvisí s osobou nebo subjektem, ke kterému
		je polo¾ka pøipojena.</p>

		<span class="optionhead">Zobrazit</span>
		<p>Toto políèko od¹krtnìte, pokud nechcete, aby byl náhled tohoto média zobrazen na stránce osoby. Toto mù¾ete udìlat, kdy¾ je obrázek
		ji¾ souèástí alba, které bylo pøipojeno k té¾e osobì.</p>

		<span class="optionhead">Místo poøízení/vytvoøení</span>
		<p><p>Tato sekce je ve výchozím stavu sbalena. Pro její rozbalení kliknìte na výraz "Místo poøízení/vytvoøení" nebo na ¹ipku vedle nìj. Znáte-li název místa,
		kde byla fotografie poøízena, zapi¹te jej do pole oznaèeného "Místo poøízení/vytvoøení".</p>

		<span class="optionhead">Zemìpisná ¹íøka a délka</span>
		<p>Pokud jsou s va¹í mediální polo¾kou spojeny souøadnice zemìpisné ¹íøky a délky, zapi¹te je sem a pomù¾ete ostatním pøesnì urèit místo.
		Jinak mù¾ete pro nastavení zemìpisné ¹íøky a délky místa média pou¾ít funkci geokódování Google Map. KLiknutím na tlaèítko "Zobrazit/skrýt klikací mapu"
		se otevøe Google Map.</p>

		<span class="optionhead">Pøiblí¾ení</span>
		<p>Zadejte úroveò pøiblí¾ení nebo upravte ovládací prvek pøiblí¾ení v Google Map pro nastavení úrovnì pøiblí¾ení. Tato volba je dostupná pouze, kdy¾ jste obdr¾eli "klíè"
		od Google a zapsali jej do va¹eho nastavení map v TNG.</p>

		<p>Pozn.: Zemìpisná ¹íøka/délka/pøiblí¾ení je u mediálních polo¾ek pouze z informativních dùvodù. Místo není pøesnì urèeno na ¾ádné mapì ve veøejné oblasti.</p>

		<span class="optionhead">Klikací mapa</span>
		<p>Tato sekce je ve výchozím stavu sbalena. Pro její rozbalení klinìte na výraz "Klikací mapa" nebo na ¹ipku vedle nìj. V této sekci mù¾ete spojit
		rùzné èásti obrázku s osobami, rodinami, prameny, úlo¾i¹ti nebo místy ve va¹í databázi nebo s externími adresami URL.</p>

		<p><strong>Pozn.</strong>: Pro pou¾ití této funkce musí být mediální polo¾ka ve formátu JPG, GIF nebo PNG.</p>

		<p>U ka¾dé oblasti, kterou chcete propojit, nejprve vyberte strom a typ entity (osoba, rodina, atd.), NEBO zadejte název a externí adresu URL jiné webové stránky.
    Poté definujte oblast nakreslením obdélníku na obrázek pomocí
    ukazatele my¹i. Zaènìte kliknutím na levý horní roh obdélníku, poté podr¾te my¹ a pohybujte ji dolù a doprava, abyste obdélník nakreslili. Kdy¾
    se dostanete do pravého dolního rohu obdélníku, uvolnìte my¹. Tím
    vyberete souøadnice obrázku. Po výbìru souøadnic se zobrazí vyskakovací okno, které vám umo¾ní najít nebo zadat ID entity. Zadejte
    celé jméno nebo èást jména nebo ID pro nalezení mo¾ných shod a poté vyberte správnou polo¾ku ze zobrazených kandidátù.
    Pole se zavøe a nad obrázek bude pøidáno pole pro tuto oblast. Chcete-li vybrat existující region, kliknìte na políèko. Poté mù¾ete pole pøesunout pøeta¾ením, nebo kliknutím na "X"
    v pravém horním rohu jej odstranit.</p>

		<p>Tento postup mù¾ete opakovat pro dal¹í oblasti. Ka¾dý nový kód bude vlo¾en na konec obsahu pole Klikací mapa.</p>

		<p>Chcete-li rùzné èásti va¹eho obrázku spojit s rùznými stránkami nebo zobrazit krátké zprávy pøi pøemístìní kursoru my¹í nad tyto èásti, zapi¹te do tohoto pole
		potøebný kód mapy obrázku. Vytvoøit svoji vlastní mapu obrázku mù¾ete podle sekce Tvorba mapy obrázku na konci stránky.</p>

	</td>
</tr>
<tr class="databack">
	<td class="tngshadow">

		<p style="float:right"><a href="#top">Top</a></p>
		<a name="delete"><p class="subheadbold">Vymazat médium</p></a>

    <p>Chcete-li odstranit jednu mediální polo¾ku, pou¾ijte zálo¾ku <a href="#search">Hledat</a> pro nalezení dané polo¾ky, a poté kliknìte na ikonu Vymazat vedle této polo¾ky. Tento øádek zmìní
		barvu a poté po odstranìní polo¾ky zmizí.  Chcete-li najednou odstranit více polo¾ek, za¹krtnìte políèko ve sloupci Vybrat vedle ka¾dé polo¾ky, kterou
    chcete odstranit, a poté kliknìte na tlaèítko "Vymazat vybrané" na stránce nahoøe</p>

	</td>
</tr>
<tr class="databack">
	<td class="tngshadow">

		<p style="float:right"><a href="#top">Nahoru</a></p>
		<a name="convert"><p class="subheadbold">Pøevést médium z jedné kolekce do jiné</p></a>
		Chcete-li pøevést mediální polo¾ky z jednoho typu média nebo "kolekce" do jiné, za¹krtnìte na zálo¾ce <a href="#search">Hledat</a> políèko vedle tìchto polo¾ek,
		poté z rozbalovacího seznamu v horní èásti stránky vedle tlaèítka "Pøevést vybrané na" vyberte novou kolekci. Na závìr kliknìte na tlaèítko "Pøevést vybrané na".
		Stránka bude zobrazena znovu s èervenou stavovou zprávou nahoøe.</p>

	</td>
</tr>
<tr class="databack">
	<td class="tngshadow">

		<p style="float:right"><a href="#top">Nahoru</a></p>
		<a name="album"><p class="subheadbold">Pøidat médium do alba</p></a>
		Chcete-li médium pøidat do alba, za¹krtnìte políèko Vybrat vedle polo¾ek, které mají být pøidány, poté z rozbalovacího seznamu v horní èásti stránky
		vedle tlaèítka "Pøidat do alba" vyberte album. Na závìr kliknìte na tlaèítko "Pøidat do alba". Média mù¾ete do alba pøidat také z Admin/Alba.</p>

	</td>
</tr>
<tr class="databack">
	<td class="tngshadow">

		<p style="float:right"><a href="#top">Nahoru</a></p>
		<a name="sort"><p class="subheadbold">Seøadit média</p></a>
		<p>Standardnì jsou média spojená s osobou, rodinou, pramenem, úlo¾i¹tìm pramenù nebo místem seøazena v poøadí, ve kterém byla k tomuto subjektu pøipojena. Toto poøadí
		mù¾ete zmìnit na zálo¾ce Media/Seøadit.</p>

		<span class="optionhead">Strom, Typ odkazu, Kolekce:</span>
		<p>Zvolte strom spojený se subjektem, u kterého chcete zmìnit poøadí médií. Dále vyberte typ odkazu (osoba, rodina, pramen, úlo¾i¹tì pramenù nebo místo) a
		kolekci, kterou chcete pøetøídit.</p>

		<span class="optionhead">ID èíslo:</span>
		<p>Zapi¹te ID èíslo nebo název (pouze místa) subjektu. Pokud neznáte ID èíslo nebo pøesný název místa, kliknutím na ikonu lupy je mù¾ete vyhledat.
    Po nalezení po¾adovaného subjektu kliknìte na odkaz "Vybrat" vedle tohoto subjektu. Okno se zvøe a vybrané ID èíslo se objeví v poli ID èíslo.</p>

    <span class="optionhead">Spojeno s urèitou událostí</span>
		<p>Pokud chcete pøetøídit mediální polo¾ky pøipojené k urèité události spojené s pøipojeným subjektem, za¹krtnìte políèko oznaèené "Spojeno s urèitou událostí" PO
		vyplnìní v¹ech ostatních polí, vèetnì ID èísla. Objeví se dal¹í rozbalovací seznam, ve kterém vyberete
		tuto urèitou událost (nepovinné).</p>

		<span class="optionhead">Postup tøídìní</span>
		<p>Po výbìru nebo zápisu ID èísla kliknìte na tlaèítko "Pokraèovat..." a zobrazí se v¹echna média vybraného subjektu a kolekce v jejich aktuálním poøadí.
		Chcete-li polo¾ky pøetøídit, kliknìte u nìkteré polo¾ky na oblast "Táhnout" a pøi stisknutém tlaèítku my¹i pøesuòte polo¾ku na po¾adované místo
		v seznamu. Je-li polo¾ka na po¾adovaném místì, uvolnìte tlaèítko my¹i ("táhni a pus»"). V tomto okam¾iku budou ulo¾eny zmìny.</p>

		<p>Dal¹í mo¾ností pøetøídìní polo¾ek je zápis po sobì jdoucích èísel do malých políèek vedle oblasti "Táhnout", poté kliknutí na odkaz "Go" pod políèkem nebo stisknutí Enteru.
		Mù¾e to být výhodné, pokud je seznam pøíli¹ dlouhý a nevejde se na jednu obrazovku.</p>

		<p>Jakoukoli polo¾ku mù¾ete pøesunout na zaèátek seznamu kliknutím na ikonu "Top" nad políèkem s poøadím.</p>

		<span class="optionhead">Výchozí fotografie</span>
		<p>Pøi tøídìní mù¾ete zvolit jakoukoli zobrazenou fotografii jako <strong>Výchozí fotografii</strong> aktuálního subjektu. Znamená to, ¾e se náhled zvoleného obrázku
		objeví ve schématu vývodu a v titulech stránek s názvem nebo popisem aktuálního subjektu. Chcete-li nastavit nebo vymazat oznaèení Výchozí fotografie, podr¾te
		kurzor my¹i nad obrázkem v seznamu, a poté kliknìte na jednu z voleb, které se objeví: "Nastavit jako výchozí" nebo "Odstranit". Aktuální výchozí fotografii
		lze odstranit také kliknutím na odkaz "Odstranit výchozí fotografii" na stránce nahoøe.</p>

	</td>
</tr>
<tr class="databack">
	<td class="tngshadow">

		<p style="float:right"><a href="#top">Nahoru</a></p>
		<a name="thumbs"><p class="subheadbold">Náhledy</p></a>

		<span class="optionhead">Vytvoøit náhledy</span>
		<p>Pop kliknutí na tlaèítko "Vygenerovat" pod touto volbou, vytvoøí TNG automaticky náhledy v¹ech obrázkù formátu JPG, GIF nebo
		PNG, které nemají existující náhledy. Standardnì bude název obrázku stejný jako je název velkého obrázku a bude obsahovat
		pøedponu a/nebo pøíponu, které jsou definovány v Základním nastavení. Za¹krtnutím políèka oznaèeného "Obnovit existující náhledy" vytvoøíte
		náhledy v¹ech obrázkù, vèetnì tìch, které je ji¾ mají. Políèko "Obnovit názvy cest k náhledùm, kde soubor neexistuje" za¹krtnìte, pokud
		si myslíte, ¾e máte nìkteré náhledy, které ukazují na neplatné soubory. To zpùsobí, ¾e TNG pøehodnotí názvy cest u náhledù pøed obnovením náhledù.
		Bez této funkce by docházelo k opìtovnému vytváøení nìkterých neplatných názvù náhledù.</p>

		<p><strong>POZN.</strong>: Pokud nevidíte sekci Vytvoøit náhledy, vá¹ server nepodporuje knihovnu GD image.</p>

		<span class="optionhead">Pøiøadit výchozí fotografie</span>
		<p>Tato volba vám umo¾ní nastavit jako výchozí fotografii první fotografii u ka¾dé osoby, rodiny nebo pramenu
		(ta, která bude zobrazena ve schématu vývodu, rodiny a nahoøe na ka¾dé stránce, která je s daným subjektem spojena). Pøiøazení mù¾e být provedeno
		pro v¹echny osoby, rodiny, prameny a úlo¾i¹tì pramenù v urèitém stromu výbìrem tohoto stromu z rozbalovacího seznamu. Za¹krtnutím políèka
		oznaèeného "Pøepsat existující nastavení" nastavíte výchozí fotografie bez ohledu na to, co bylo nastaveno døíve. Ponechání tohoto políèka
		neza¹krtnutého vám umo¾ní ponechat døíve nastavené výchozí fotografie, co¾ mù¾e být u¾iteèné zejména po nahrání souboru GEDCOM, ve kterém ji¾ jsou nastaveny výchozí fotografie.</p>
	</td>
</tr>
<tr class="databack">
	<td class="tngshadow">

		<p style="float:right"><a href="#top">Nahoru</a></p>
		<a name="import"><p class="subheadbold">Import médií</p></a>

		<span class="optionhead">Cíl</span>
		<p>Vytvoøení záznamu média pro ka¾dý fyzický soubor ve va¹í slo¾ce médií s názvem souboru jako titulem ka¾dého záznamu.</p>

		<span class="optionhead">Pou¾ití</span>
		<p>Chcete-li import provést, zvolte nejprve kolekci (nebo vytvoøte novou kolekci) a strom (pokud mají být vkládané polo¾ky spojeny s urèitým stromem), poté kliknìte na tlaèítko "Import".
		Existuje-li ji¾ pro polo¾ku záznam, nový záznam se nevytvoøí. "Klíèem" (který urèí, zda ji¾ záznam existuje nebo ne) je
		název souboru a strom. Pokud importujete stejnou polo¾ku do více stromù (nebo pokud byla polo¾ka kdysi importována do "v¹ech stromù" a jindy
		jen do urèitého stromu), TNG nepozná, ¾e ji¾ máte záznam pro tuto polo¾ku a vytvoøí jej znovu.</p>

	</td>
</tr>
<tr class="databack">
	<td class="tngshadow">

		<p style="float:right"><a href="#top">Nahoru</a></p>
		<a name="upload"><p class="subheadbold">Nahrání médií</p></a>

		<span class="optionhead">Cíl</span>
		<p>Dávkové nahrání více polo¾ek médií, jejich opatøení tituly a popisy, vèetnì jejich pøipojení k osobám, rodinám, pramenùm nebo místùm
			pøímo z této obrazovky.</p>

		<span class="optionhead">Pou¾ití</span>
		<p>Chcete-li tuto funkci pou¾ít, zvolte nejprve kolekci a strom (pokud mají být vkládané polo¾ky spojeny s urèitým stromem), poté kliknìte na "Pøidat soubory" a z va¹eho poèítaèe vyberte soubory pro nahrání. Vìt¹ina prohlí¾eèù (mimo Internet Explorer) vám umo¾ní soubory chytit a pøetáhnout
			z jiného okna pøímo do bílé oblasti ve støedu obrazovky. Chcete-li zvolit jako cíl pro nahrání va¹ich souborù podslo¾ku v rámci zvolené slo¾ky, zapi¹te do pole "Slo¾ka" její název nebo pou¾ijte tlaèítko
			"Vybrat" pro výbìr podslo¾ky, která ji¾ existuje. Nechcete-li soubory ulo¾it do podslo¾ky, nechte pole Slo¾ka prázdné.
      Po dokonèení výbìru souborù a jejich umístìní mù¾ete zahájit nahrání v¹ech souborù najednou kliknutím
			na tlaèítko "Spustit nahrání" na stránce nahoøe. Nebo mù¾ete nahrát soubory jednotlivì kliknutím na tlaèítko "Spustit" vedle pøíslu¹ného souboru.
			Po ukonèení nahrání mù¾ete pøidat nový titul nebo popis, nebo pøipojit polo¾ku k urèitému záznamu ve va¹í databázi, nebo je v¹echny vymazat.</p>

		<span class="optionhead">Zmìna titulu a popisu</span>
		<p>Po nahrání souboru se zobrazí pole pro titul a popis. Chcete-li zmìnit výchozí hodnoty, nové údaje zapi¹te a kliknìte na "Ulo¾it" ve støedu oblasti.
			Dal¹í údaje mù¾ete pozdìji pøidat z obrazovky Úprava média.</p>

		<span class="optionhead">Pøidat odkazy</span>
		<p>Chcete-li pøipojit urèitou mediální polo¾ku k polo¾ce va¹í databáze, poèkejte na ukonèení nahrání. Poté kliknìte na tlaèítko "Odkazy na médium" na stejném øádku.
			Zapi¹te ID èíslo a kliknìte na "Pøidat" nebo pro vyhledání a výbìr èísla ID pou¾ijte volbu Najít.</p>
		<p>Chcete-li pøipojit více mediálních polo¾ek najednou ke stejné polo¾ce, za¹krtnìte zátr¾ku na øádku u ka¾dé polo¾ky (nebo pou¾ijte tlaèítko "Vybrat v¹e" pro výbìr v¹ech nahraných
			polo¾ek), a poté pou¾ijte pole na obrazovce dole pro dokonèení operace. Zapi¹te ID èíslo nebo pro vyhledání pou¾ijte volbu Najít. Je-li èíslo ID
			v poli ID a vybrána byla alespoò jedna mediální polo¾ka, kliknìte na tlaèítko "Pøipojit k vybraným" pro vytvoøení odkazù.</p>
	</td>
</tr>

</table>
</body>
</html>
