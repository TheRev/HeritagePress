<?php
include("../../helplib.php");
echo help_header("Nápovìda: U¾ivatelé");
?>

<body class="helpbody">
<a name="top"></a>
<table width="100%" border="0" cellpadding="10" cellspacing="2" class="tblback normal">
<tr class="fieldnameback">
	<td class="tngshadow">
		<p style="float:right; text-align:right" class="smaller menu">
			<a href="https://tng.community" target="_blank" class="lightlink">TNG Forum</a> &nbsp; | &nbsp;
			<a href="https://tng.lythgoes.net/wiki" target="_blank" class="lightlink">TNG Wiki</a><br />
			<a href="templateconfig_help.php" class="lightlink">&laquo; Nápovìda: Nastavení ¹ablony</a> &nbsp; | &nbsp;
			<a href="trees_help.php" class="lightlink">Nápovìda: Stromy &raquo;</a>
		</p>
		<span class="largeheader">Nápovìda: U¾ivatelé</span>
		<p class="smaller menu">
			<a href="#search" class="lightlink">Hledat</a> &nbsp; | &nbsp;
			<a href="#add" class="lightlink">Pøidat nebo Upravit</a> &nbsp; | &nbsp;
			<a href="#delete" class="lightlink">Vymazat</a> &nbsp; | &nbsp;
			<a href="#review" class="lightlink">Pøezkoumat</a> &nbsp; | &nbsp;
			<a href="#rights" class="lightlink">Pøístupová práva</a> &nbsp; | &nbsp;
			<a href="#limits" class="lightlink">Omezení pøístupu</a> &nbsp; | &nbsp;
			<a href="#email" class="lightlink">Email</a>
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
    <p>Nalezení existujících u¾ivatelù vyhledáním celého nebo èásti <strong>U¾ivatelského jména, popisu nebo skuteèného jména</strong> nebo <strong>emailu</strong>. Pro zú¾ení va¹eho hledání za¹krtnìte
		"Zobrazit pouze u¾ivatele s administrátorským oprávnìním". Výsledkem hledání bez zadaných voleb a hodnot ve vyhledávacích polích bude seznam v¹ech u¾ivatelù ve va¹í databázi.</p>

		<p>Vyhledávací kritéria, která zadáte na této stránce, budou uchována, dokud nekliknete na tlaèítko <strong>Obnovit</strong>, které znovu obnoví v¹echny výchozí hodnoty.</p>

		<span class="optionhead">Akce</span>
		<p>Tlaèítko Akce vedle ka¾dého výsledku hledání vám umo¾ní upravit nebo odstranit tento výsledek. Chcete-li najednou odstranit více záznamù, za¹krtnìte políèko ve sloupci
		<strong>Vybrat</strong> u ka¾dého záznamu, který má být odstranìn a poté kliknìte na tlaèítko "Vymazat oznaèené" na zaèátku seznamu. Pro za¹krtnutí nebo vyèi¹tìní v¹ech výbìrových políèek najednou
    mù¾ete pou¾ít tlaèítka <strong>Vybrat v¹e</strong> nebo <strong>Vyèistit v¹e</strong>.</p>

	</td>
</tr>
<tr class="databack">
	<td class="tngshadow">

		<p style="float:right"><a href="#top">Nahoru</a></p>
		<a name="add"><p class="subheadbold">Pøidání nového u¾ivatele</p></a>
		<p>Nastavení u¾ivatelských záznamù pro va¹e náv¹tìvníky vám umo¾ní pøidìlit jim zvlá¹tní pøístupová práva, která vstoupí v platnost jejich pøihlá¹ením pomocí u¾ivatelského jména a hesla.
		První u¾ivatel, kterého vytvoøíte, by mìl být administrátor (nìkdo, kdo má v¹echna práva a není omezen ¾ádným stromem, obvykle to jste vy). Pokud si nepøidìlíte dostateèná (administrátorská) oprávnìní, 
		nebudete schopni dostat se zpìt do administrátorské oblasti programu. Zapomenete-li své u¾ivatelské jméno, jdìte na stránku Pøihlá¹ení a zadejte svoji emailovou adresu,
		která je spojena s va¹ím u¾ivatelským úètem a u¾ivatelské jméno vám bude zasláno emailem. Zapomenete-li své heslo, zadejte svoji emailovou adresu a u¾ivatelské jméno a bude vám zasláno
		nové, doèasné heslo. Po pøihlá¹ení pomocí nového hesla se vra»te do Admin/U¾ivatelé a nastavte si heslo na nìjaké zapamatovatelné.</p>
    
    <p>Chcete-li pøidat nového u¾ivatele, kliknìte na zálo¾ku <strong>Pøidat nový</strong> a pak vyplòte formuláø. Chcete-li upravit existujícího u¾ivatele, kliknìte na ikonu Upravit vedle tohoto u¾ivatele.
    Význam polí pøi pøidání nebo úpravì u¾ivatele je následující:</p>

		<span class="optionhead">Popis</span>
		<p>Va¹emu u¾ivateli mù¾ete pøidat struèný popis, abyste vìdìli, o koho jde. Mù¾ete napø. zapsat "Administrátor stránek" nebo "Teta Marta".</p>

		<span class="optionhead">U¾ivatelské jméno</span></span>
		<p>Jednoznaèný jednoslovný identifikátor tohoto u¾ivatele (stejné u¾ivatelské jméno nemohou mít dva u¾ivatelé). U¾ivatel bude pøi pøihlá¹ení po¾ádán o zadání svého u¾ivatelského jména v délce max. 20 znakù.</p> 

		<span class="optionhead">Heslo</span>
		<p>Dùvìrné slovo nebo øetìzec znakù (bez mezer), které tento u¾ivatel musí také pøi pøihlá¹ení zadat. Pøi zápisu do tohoto pole budou zapisované znaky
		na obrazovce pro zachování utajení nahrazovány hvìzdièkami nebo jinými podobnými znaky. Délka max. 20 znakù. Heslo je v databázi za¹ifrováno
		a nelze jej nikým zobrazit, ani tímto u¾ivatelem nebo programem Next Generation.</p>

		<span class="optionhead">Skuteèné jméno</span>
		<p>Aktuální jméno (pokud je platné) u¾ivatele, které odpovídá tìmto údajùm.</p>

		<span class="optionhead">Telefon, email, internetové stránky, adresa, mìsto, kraj/provincie, PSÈ, zemì, poznámky</span>
		<p>Nepovinné údaje, které se týkají u¾ivatele.</p>

		<span class="optionhead">Neposílat tomuto u¾ivateli hromadné emaily</span>
		<p>Toto políèko za¹krtnìte, pokud nechcete, aby tomuto u¾ivateli byly posílány hromadné emaily (viz ní¾e).</p>

		<span class="optionhead">Strom / ID èíslo osoby</span>
		<p>Pokud tento u¾ivatel odpovídá nìkteré osobì z va¹í databáze, mù¾ete zde oznaèit strom a ID èíslo osoby jeho záznamu.
		Umo¾ní to zobrazit tomuto u¾ivateli v¹echny údaje ze svého záznamu, i kdy¾ tento záznam není obsa¾en v pøipojeném stromu nebo vìtvi.</p>

		<span class="optionhead">Zakázat pøístup</span>
		<p>Za¹krtnutím tohoto políèka zabráníte tomuto u¾ivateli pøihlásit se, ani¾ byste vymazali jeho celý u¾ivatelský úèet.</p>

 		<span class="optionhead">Udìlen souhlas</span>
		<p>Za¹krtnutím tohoto políèka u¾ivatel udìlil souhlas s ulo¾ením svých informací do va¹í databáze.</p>

		<span class="optionhead">Role a pøístupová práva</span>
		<p>Viz <a href="#rights">ní¾e, kde jsou uvedeny podrobnosti o rolích a pøístupových právech</a>, která mohou být u¾ivateli pøidìlena.</p>

		<p><span class="optionhead">Povinná pole:</span> Musíte zadat u¾ivatelské jméno, heslo a popis u¾ivatele. V¹echna ostatní pole jsou nepovinná, ale doporuèujeme
		zadat emailovou adresu pro pøípad, ¾e zapomenete své u¾ivatelské jméno nebo heslo.</p>

	</td>
</tr>
<tr class="databack">
	<td class="tngshadow">

		<p style="float:right"><a href="#top">Nahoru</a></p>
		<a name="delete"><p class="subheadbold">Vymazání u¾ivatelù</p></a>
	  <p>Chcete-li odstranit u¾ivatele, pou¾ijte zálo¾ku <a href="#search">Hledat</a> k nalezení u¾ivatele, a poté kliknìte na ikonku Vymazat vedle záznamu tohoto u¾ivatele. Tento øádek zmìní
		barvu a poté po odstranìní polo¾ky u¾ivatel zmizí.</p>

	</td>
</tr>
<tr class="databack">
	<td class="tngshadow">

		<p style="float:right"><a href="#top">Nahoru</a></p>
		<a name="review"><p class="subheadbold">Pøezkoumat</p></a>

		<p>Kliknutím na zálo¾ku "Pøezkoumat" mù¾ete spravovat nové u¾ivatelské registrace. Tyto u¾ivatelské záznamy nebudou aktivní, dokud je nejdøív neupravíte a neulo¾íte. Poté, co se stane 
		záznam aktivním, u¾ nebude vidìt na zálo¾ce Pøezkoumat. Místo toho jej bude mo¾no nalézt na zálo¾ce "Hledat".</p>
		
		<p>Nové u¾ivatelské záznamy na zálo¾ce Pøezkoumat mohou být vymazány nebo upravovány stejným zpùsobem jako øádné u¾ivatelské záznamy. Pøi úpravì záznamu nového u¾ivatele
		si pov¹imnìte následujícího:</p>
		
		<span class="optionhead">Vyrozumìt tohoto u¾ivatele, ¾e byl úèet aktivován</span>
		<p>Za¹krtnutím tohoto políèka po¹lete emailem novému u¾ivateli informaci o aktivaci úètu (po ulo¾ení stránky). Text zprávy se objeví v poli pod
		touto volbou. Pøed odesláním mù¾ete provést zmìny tohoto textu.</p>

	</td>
</tr>
<tr class="databack">
	<td class="tngshadow">

		<p style="float:right"><a href="#top">Nahoru</a></p>
		<a name="rights"><p class="subheadbold">Role a pøístupová práva</p></a>

		<p>"Pøístupové právo" je to, co mù¾e u¾ivatel dìlat poté, co se pøihlásí. "Role" je pøeddefinovaná sada pøístupových práv, tak¾e
		pokud vyberete jinou roli, seznam zvolených pøístupových práv (na pravé stranì stránky) se zmìní (pøístupová práva "Povolit"
		na konci sloupce se výbìrem role nezmìní). Výbìrem role "Vlastní" mù¾ete u¾ivateli definovat svoji vlastní sadu pøístupových práv.
		Nìkteré role v sobì zahrnují pøipojení u¾ivatele k urèitému stromu,
		v jiných rolích nebude u¾ivatel pøipojen k ¾ádnému stromu. Role, kterou vyberete, mù¾e pak zpùsobit, ¾e pole pøipojený strom nebude za¹krtnuto.</p>
		
		<p>U¾ivateli mohou být pøipojena následující pøístupová práva:</p>
		
		<span class="optionhead">Povolit pøidávat nové záznamy</span>
		<p>U¾ivatel mù¾e v administrátorské oblasti pøidat nové záznamy, vèetnì médií.</p>

		<span class="optionhead">Povolit pøidávat pouze média</span>
		<p>U¾ivatel mù¾e v administrátorské oblasti pøidat nová média, nic jiného.</p>

		<span class="optionhead">Bez práv pøidávat</span>
		<p>U¾ivatel nesmí pøidávat ¾ádné nové údaje.</p>

		<span class="optionhead">Povolit úpravy existujících záznamù</span>
		<p>U¾ivatel mù¾e v administrátorské oblasti upravovat existující záznamy, vèetnì médií.</p>

		<span class="optionhead">Povolit úpravy pouze médií</span>
		<p>U¾ivatel mù¾e v administrátorské oblasti upravovat existující média, nic jiného.</p>

		<span class="optionhead">Povolit pøedlo¾it úpravy pro pøezkoumání administrátorem</span>
		<p>U¾ivatel nemù¾e v administrátorské oblasti záznamy upravovat. Pøedbì¾né zmìny mù¾e udìlat ve veøejné oblasti kliknutím na malou ikonu
		Upravit vedle pøíslu¹ných událostí na stránkách osoby a rodiny. Zmìny se nestanou trvalými, dokud nebudou schváleny administrátorem.</p>

		<span class="optionhead">Bez práv upravovat</span>
		<p>U¾ivatel nesmí provádìt úpravy existujících záznamù.</p>

		<span class="optionhead">Povolit vymazat existující záznamy</span>
		<p>U¾ivatel mù¾e v administrátorské oblasti vymazat existující záznamy, vèetnì médií.</p>

		<span class="optionhead">Povolit vymazat pouze média</span>
		<p>U¾ivatel mù¾e v administrátorské oblasti vymazat média, nic jiného.</p>

		<span class="optionhead">Bez práv vymazat</span>
		<p>U¾ivatel nesmí vymazat ¾ádné existující záznamy.</p>

		<p>Následující pøístupová práva jsou nezávislá na zvolené roli:</p>

    <span class="optionhead">Povolit prohlí¾ení údajù ¾ijících osob</span>
		<p>U¾ivatel mù¾e ve veøejné oblasti prohlí¾et údaje ¾ijících osob.</p>

    <span class="optionhead">Povolit prohlí¾ení údajù osob oznaèených jako neveøejné</span>
		<p>U¾ivatel mù¾e ve veøejné oblasti prohlí¾et údaje osob oznaèených jako neveøejné.</p>
    
    <span class="optionhead">Povolit zobrazení neveøejných poznámek</span>
		<p>U¾ivatel mù¾e ve veøejné oblasti prohlí¾et poznámky oznaèené jako neveøejné.</p>

	  <span class="optionhead">Povolit sta¾ení souboru GEDCOM</span>
		<p>U¾ivatel mù¾e ve veøejné oblasti pou¾ít zálo¾ku GEDCOM ke sta¾ení souboru GEDCOM. Toto potlaèí nastavení pro ka¾dý strom v Administrace/Stromy.</p>

	  <span class="optionhead">Povolit sta¾ení souboru PDF</span>
		<p>U¾ivatel mù¾e ve veøejné oblasti na rùzných stránkách pou¾ít volbu PDF pro vytvoøení souboru PDF. Toto potlaèí nastavení pro ka¾dý strom v Administrace/Stromy.</p>

		<span class="optionhead">Povolit prohlí¾ení údajù CJKSpd</span>
		<p>U¾ivatel mù¾e ve veøejné oblasti prohlí¾et údaje CJKSpd.</p>

		<span class="optionhead">Povolit úpravy u¾ivatelského profilu</span>
		<p>U¾ivatel mù¾e z odkazu ve veøejné oblasti upravovat svùj u¾ivatelský profil (u¾ivatelské jméno, heslo, atd.).</p>

	</td>
</tr>
<tr class="databack">
	<td class="tngshadow">
		
		<p style="float:right"><a href="#top">Nahoru</a></p>
		<a name="rights"><p class="subheadbold">Omezení pøístupu</p></a>

		<p>Toto definuje omezení u¾ivatelských práv. V¹ichni u¾ivatelé (vèetnì anonymních náv¹tìvníkù) mohou v¾dy vidìt údaje zesnulých osob. Zde nejsou nutná ¾ádná práva 
		nebo omezení pøístupù.</p>
		
		<span class="optionhead">Omezit na strom/vìtev</span>
		<p>Chcete-li omezit pøístupové právo u¾ivatele na urèitý strom, vyberte tento strom zde. Chcete-li omezit pøístupová práva na urèitou vìtev
		ve vybraném stromì, vyberte tuto vìtev také. Pøipojením vìtve k u¾ivateli nezabráníte tomuto u¾ivateli zobrazit jiné osoby, které nejsou souèástí této vìtve.</p>

    <span class="optionhead">Uplatnit práva na více stromù</span>
		<p>Chcete-li omezit práva u¾ivatele na více stromù, vyberte tuto mo¾nost a poté pomocí klávesy Ctrl tyto stromy vyberte. Kdy¾ se u¾ivatel poprvé pøihlásí, 
     bude vybrán první strom z tohoto seznamu. U¾ivatel se mù¾e pøepínat mezi stromy pomocí rozbalovací nabídky v horní èásti stránky v pravém rohu nabídky Administrace
     (rozbalovací nabídka je viditelná pouze v pøípadì, ¾e je k dispozici výbìr jiného stromu). Následné pøihlá¹ení ze stejného prohlí¾eèe zpùsobí, ¾e na zaèátku bude
     vybrán naposledy pou¾itý strom. U¾ivatel se mù¾e pøepínat mezi stromy také z veøejné stránky Stromy. V tomto re¾imu nelze provést výbìr vìtve.</p>

	</td>
</tr>
<tr class="databack">
	<td class="tngshadow">

		<a name="email"><p class="subheadbold">Email</p></a>
		<p>tato zálo¾ka umo¾òuje poslat email v¹em u¾ivatelùm nebo v¹em u¾ivatelùm pøipojeným k urèitému stromu/vìtvi.</p>
		
		<span class="optionhead">Pøedmìt</span>
		<p>Pøedmìt va¹eho emailu.</p>

		<span class="optionhead">Text</span>
		<p>Tìlo va¹í emailové zprávy.</p>

		<span class="optionhead">Strom</span>
		<p>Pokud chcete poslat tuto zprávu pouze u¾ivatelùm pøipojeným k urèitému stromu, tento strom vyberte zde.</p>

		<span class="optionhead">Vìtev</span>
		<p>Pokud chcete poslat tuto zprávu pouze u¾ivatelùm pøipojeným k urèité vìtvi uvnitø vybraného stromu, tuto vìtev vyberte zde.</p>

	</td>
</tr>

</table>
</body>
</html>
