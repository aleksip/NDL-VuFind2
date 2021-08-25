# Markdownissa käytettävissä olevat finna-tagit

<finna-tabs>
 <h2 slot="label">&lt;finna-truncate&gt;</h2>
 <div slot="content">

### finna-truncate

```html
<finna-truncate>
  Piiloon jäävä teksti tulee tähän väliin.
</finna-truncate>
```

<finna-truncate>
  Piiloon jäävä teksti tulee tähän väliin.
</finna-truncate>

Alla olevat esimerkit on kehystetty finna-panel:iin, osittain selkeyden vuoksi ja osittain sen vuoksi että tällä hetkellä truncate-toiminto avaa ja sulkee kaikki samassa emoelementissä olevat truncatet. Samalla tulee demottua että finna-truncate:n voi laittaa finna-panel:in sisään.

```html
<finna-panel>
 <finna-truncate>
  Luonnollisesti **Markdownia** voi käyttää myös finna-truncate:n sisällä.
 </finna-truncate>
</finna-panel>
```

<finna-panel>
 <finna-truncate>
  Luonnollisesti **Markdownia** voi käyttää myös finna-truncate:n sisällä.
 </finna-truncate>
</finna-panel>

Oletuksena ei näytetä yhtään riviä, mutta näytettävien rivien määrän voi asettaa rows-attribuutilla:

```html
<finna-truncate rows="2">
```

<finna-panel>
 <finna-truncate rows="2">
  Tämä näyttää
  kaksi ensimmäistä
  riviä.
 </finna-truncate>
</finna-panel>

Oman etiketin voi asettaa seuraavasti:

 ```html
 <finna-truncate>
  <span slot="label">Oma etiketti</span>

  Sisältö
 </finna-truncate>
 ```

<finna-panel>
 <finna-truncate>
  <span slot="label">Oma etiketti</span>

  Sisältö
 </finna-truncate>

</finna-panel>

 </div>

 <h2 slot="label">&lt;finna-panel&gt;</h2>
 <div slot="content">

### finna-panel

```html
<finna-panel>
 <h3 slot="heading">Otsikko</h3>

 Sisältö. Otsikon ja sisällön väliin on jätettävä tyhjä rivi.
</finna-panel>
```

<finna-panel>
 <h3 slot="heading">Otsikko</h3>

 Sisältö. Huomioi otsikon ja sisällön väliin jätettävä tyhjä rivi.
</finna-panel>

Oletusotsikkotaso on h3, mutta sitä voi muuttaa:

```html
<h4 slot="heading">Otsikko</h4>
```

Otsikkotason muutos ei näy visuaalisesti, mutta sillä on merkitystä saavutettavuuden kannalta.

<finna-panel collapsed="false">
 <h4 slot="heading">Tämä finna-panel on oletuksena auki</h4>

 Oletuksena auki olevan paneelin saa tehtyä lisäämällä collapsed-attribuutin:

 ```html
 <finna-panel collapsed="false">
 ```

 #### Rikasta sisältöä

 Elementin sisällä voi käyttää **Markdownia**.
  
 Finna-panel on täsmälleen sama <a href="https://natlibfi.github.io/NDL-VuFind-ui-components/?p=molecules-finna-panel-collapsible">komponenttikirjaston komponentti</a> joka on jo käytössä <a href="https://finna.fi/OrganisationInfo/Home?id=NLF#86154">organisaatiosivuilla</a>.

 <finna-panel>
  <h5 slot="heading">Maatuskapaneeli</h5>

  Paneeleita voi jopa laittaa sisäkkäin.
 </finna-panel>

</finna-panel>

<finna-panel collapsible="false">
  <h5 slot="heading">Aina auki oleva paneeli</h5>

  Lisäattribuutilla collapsible voi myös tehdä paneelista aina auki olevan version:

  ```html
  <finna-panel collapsible="false">
  ```
</finna-panel>

<finna-panel>
 Jos finna-panel:iin ei laita otsikkotagia, saa tällaisen kehyksen.
</finna-panel>

 </div>

 <h2 slot="label">&lt;finna-list&gt;</h2>
 <div slot="content">

### finna-list

finna-list näyttää suosikkilistan. Finna-list tukee attribuutteina kaikkia `userlistEmbed()`-PHP-helperin parametreja, ja välittää ne helperille. Lopputuloksen pitäisi olla identtinen PHP-helper-kutsun kanssa.

```html
<finna-list id="1"></finna-list>
```

<finna-list id="1" heading-level="4"></finna-list>

 </div>

 <h2 slot="label">&lt;finna-tabs&gt;</h2>
 <div slot="content">

### finna-tabs

```html
<finna-tabs>
 <h3 slot="label">Ensimmäinen</h3>
 <div slot="content">
  Ensimmäisen välilehden sisältö.
 </div>
 <h3 slot="label">Toinen</h3>
 <div slot="content">
  Toisen välilehden sisältö. Viimeisen välilehden jälkeen on jätettävä tyhjä rivi.
 </div>
 
</finna-tabs>
```

<finna-tabs>
 <h3 slot="label">Ensimmäinen</h3>
 <div slot="content">
  Ensimmäisen välilehden sisältö.
 </div>
 <h3 slot="label">Toinen</h3>
 <div slot="content">
  Toisen välilehden sisältö. Viimeisen välilehden jälkeen on jätettävä tyhjä rivi.
 </div>

</finna-tabs>

Myös muun kuin ensimmäisen välilehden voi asettaa oletuksena aktiiviseksi käyttämällä `data-active`-attribuuttia:

```html
<h3 slot="label" data-active="true">Toinen</h3>
```

<finna-tabs>
 <h3 slot="label">Ensimmäinen</h3>
 <div slot="content">
  Ensimmäisen välilehden sisältö.
 </div>
 <h3 slot="label" data-active="true">Toinen</h3>
 <div slot="content">
  Toisen välilehden sisältö. Viimeisen välilehden jälkeen on jätettävä tyhjä rivi.
 </div>

</finna-tabs>

 </div>

 <h2 slot="label">&lt;finna-feed&gt;</h2>
 <div slot="content">

### finna-feed

 ```html
<finna-feed id="carousel-finna"></finna-feed>
```

<finna-feed id="carousel-finna"></finna-feed>

 </div>

</finna-tabs>
