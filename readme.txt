Volbyonline
===

Cíl:
---
Publikování volebních výsledků na twitter účtu

Způsob:
---
Načítání stránky z volebnímy výsledky na webu volby.cz, parsování obsahu, ukládání do db a na základě pravidel publikování jejich vystavení na twiter účtu.
O načítání se (zatím) stará minutový cron z webu http://www.cronoo.com/
(vhodné hodit do vlastního cronu, tento "cizí" měl výpadky...)

Výsledek pulikován:
---

* @volbyonline - https://twitter.com/volbyonline
* http://volbyonline.3tecky.cz

Pravidla publikování
---

* první tweet po překročení 1% spočítaných volebních okrsků 
* následuje pravidelný tweet po překroční násobku 5%
* tedy 21 tweetů na jedno monitorované volební kolo

Další informace:
---

Vyrobeno pro 2. kolo Prezidentských voleb 2013

Je počítáno s rozšířením na další (a jiné) volby:
- parlametní volby - úprava bude jednoduchá, jsou podobné těm přezidetským
- senátní volby - potřeba vytyčit cíl - bude možné si jen vybrat některý souboj
- komunální volby - zatím jsem se nezamýšlel
- volby do evropského parlamentu - dtto