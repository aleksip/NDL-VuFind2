<script type="module" src="../themes/finna2/js/finna-elements.js"></script>

# Finna-elementtiproto/demo

Finna-elementit ovat Web Components -spesifikaation mukaisesti toteutettuja omia HTML-tageja. Ne saa käyttöönsä lisäämällä vain yhden rivin HTML:ää (esimerkissä polku yksinkertaistettu):

`<script src="finna-elements.js"></script>`

Tämän jälkeen Finna-elementtejä voi käyttää lähes missä tahansa, esim. WordPressissä, Drupalissa tai muissa julkaisujärjestelmissä. Uusimmat selaimet tukevat web-komponentteja suoraan ja vanhempiin saa lisättyä tuen JavaScript-polyfilleillä.

**Tämä sivu on tehty Markdown-tiedostona, joka ei siis sisällä lainkaan PHP:tä.**

## Finna-elementit

### Tietue

`<finna-record>` hakee id:n mukaisen tietueen Finnan API:n kautta.

UI:n toteutus on täysin erillinen Finnasta/VuFindista sekä sivusta johon se on lisätty. Tämä proto-toteutus hakee ainoastaan tietueen nimen ja kuvan eikä sitä ole tyylitelty mitenkään.

`<finna-record id="sim.M016-21534"></finna-record>`

<finna-record id="sim.M016-21534"></finna-record>

### Suosikkilista

Finnan API ei tällä hetkellä tue suosikkilistojen hakemista. **-> Sivupolku: tätä varten on tehty proto-toteutus API-tuesta julkisten suosikkilistojen hakemiseen!**

`<finna-list>` hakee id:n mukaisen julkisen suosikkilistan Finnan API:n kautta.

Esimerkkinä on käytetty oikean luokkahuoneen aineistopaketin perusteella tehtyä <a href="http://finna-dev-fe.csc.fi/aleksi-test2/List/1">julkista suosikkilistaa</a>. Tätäkään proto-toteutusta ei ole tyylitelty mitenkään. `<finna-list>` lisää sisäänsä listan mukaisesti `<finna-record>`-komponentteja, mutta antaa niiden datan samalla, jolloin API-kutsuja ei tule kuin yksi. Datan mukana tulee myös tietuekohtaiset muistiinpanot.

`<finna-list id="1"></finna-list>`

<finna-list id="1" ssr="false"></finna-list>

## Tunnistettuja edelliseen toteutukseen liittyviä seikkoja

### JavaScript-riippuvuus

#### Ratkaisu: tarjotaan myös vaihtoehtoinen HTML

Kaikki selaimet ohittavat HTML-tagit, joita eivät tunnista. Oman komponentin sisään voi laittaa korvaavan sisällön vanhoille tai JavaScriptiä tukemattomille selaimille, mikäli komponenttia käytetään Finna-alustalla tai se tarjotaan Finnasta sivustolle kopioitavana embed-koodina.

```
<finna-record id="sim.M016-21534">
  <p><a href="http://finna.fi/Record/sim.M016-21534">Mainoskuva, Kar-Air Oy:n laukku</a></p>
</finna-record>
```

### Kaksinkertainen työ UI:n tekemisessä

Tämä ei välttämättä ole huono asia. Finnan-alustan ulkopuolisille sivuille upotettavat komponentit voi olla hyväkin suunnitella erikseen, jotta ne sopivat paremmin erilaisille sivuille. Samalla tulee myös testattua API:n kattavuus ja toimivuus.

#### Ratkaisu: haetaan UI-koodi AJAX-kutsuna palvelimelta

Jos kaksinkertainen työ halutaan välttää, voi komponentti oman UI-toteutuksen sijaan hakea AJAX-kutsulla saman HTML:n ja tyylit kuin mitä käytetään Finnan sivuilla.

Protossa tämä vaihtoehto on toteutettu omana `<finna-list-ajax>`-elementtinä. Protossa lisätty CustomElement-AJAX-käsittelijä pyytää nykyiseltä UserListEmbed-helperiltä saman HTML:n kuin luokkahuoneen upotetuissa listoissa. Protossa ei ole tuotu mukaan tyylejä ja JavaScriptejä, eikä muutenkaan lähdetty muokkaamaan toteutusta sen pidemmälle. Proton pitäisi kuitenkin osoittaa tämä toteutustapa mahdolliseksi.

`<finna-list-ajax id="1"></finna-list-ajax>`

<finna-list-ajax id="1"></finna-list-ajax>

### Turhat palvelinkutsut, jos käytetään Finna-alustalla

#### Ratkaisu: jos elementtiä käytetään Finna-alustalla, korvataan se palvelimella

Näin Finna-elementti ei päädy käyttäjän selaimelle, eli toteutus toimii samaan tapaan kuin nykyinen PHP-embed-kutsu.

`<finna-list id="1"></finna-list>`

<finna-list id="1"></finna-list>

Tämä on itse asiassa se mistä kaikki sai alkunsa, eli riippumatta siitä käytetäänko web-komponentteja vai ei, tällä tekniikalla voidaan toteuttaa nyt suosikkilistoissa käytössä olevat omat "laajeneva laatikko" ja "lyhennetty teksti". Lisäksi luokkahuoneen sivuja on mahdollista toteuttaa Markdownilla.
