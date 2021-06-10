# Uudet Markdownissa käytettävät finna-tagit

## finna-panel

```html
<finna-panel>
 <span slot="heading">Otsikko</span>
 Sisältö.
</finna-panel>
```

<finna-panel>
 <span slot="heading">Otsikko</span>
 Sisältö.
</finna-panel>

Oletusotsikkotaso on H2, mutta sitä voi muuttaa heading-level-attribuutilla:

`<finna-panel heading-level="4">`

<finna-panel collapsed="false" heading-level="4">
 <span slot="heading">
  Tämä finna-panel on oletuksena auki
 </span>

 Oletuksena auki olevan paneelin saa tehtyä lisäämällä collapsed-attribuutin:

 `<finna-panel collapsed="false">`

 #### Rikasta sisältöä

 Elementin sisällä voi käyttää **Markdownia**.
  
 Finna-panel on täsmälleen sama <a href="https://natlibfi.github.io/NDL-VuFind-ui-components/?p=molecules-finna-panel-collapsible">komponenttikirjaston komponentti</a> joka on jo käytössä <a href="https://finna.fi/OrganisationInfo/Home?id=NLF#86154">organisaatiosivuilla</a>.

 <finna-panel heading-level="5">
  <span slot="heading">
   Maatuskapaneeli
  </span>

  Paneeleita voi jopa laittaa sisäkkäin, vaikka se on todennäköisesti  olisikaan.
 </finna-panel>

</finna-panel>

<finna-panel collapsible="false" heading-level="5">
  <span slot="heading">
   Aina auki oleva paneeli
  </span>

  Lisäattribuutilla collapsible voi myös tehdä paneelista aina auki olevan version:

 `<finna-panel collapsible="false">`
</finna-panel>

<finna-panel>
 Jos finna-panel:iin ei laita otsikkotagia, saa tällaisen kivan kehyksen!
</finna-panel>

## finna-truncate

```html
<finna-truncate>
  Piiloon jäävä teksti tulee tähän väliin.
</finna-truncate>
```

<finna-truncate>
  Piiloon jäävä teksti tulee tähän väliin.
</finna-truncate>

Alla olevat esimerkit on kehystetty finna-panel:iin, osittain selkeyden vuoksi ja osittain sen vuoksi että tällä hetkellä truncate-toiminto avaa ja sulkee kaikki samassa emoelementissä olevat truncatet. Samalla tulee demottua että finna-truncate:n voi laittaa finna-panel:in sisään.

<finna-panel>
 <finna-truncate>
  Luonnollisesti **Markdownia** voi käyttää myös finna-truncate:n sisällä.
 </finna-truncate>
</finna-panel>

Oletuksena ei näytetä yhtään riviä, mutta näytettävien rivien määrän voi asettaa rows-attribuutilla:

`<finna-truncate rows="2">`

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
  Lorem ipsum dolor sit amet, consectetur adipiscing elit,
  sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
  Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris
  nisi ut aliquip ex ea commodo consequat.
 </finna-truncate>

</finna-panel>

## finna-list

finna-list näyttää suosikkilistan. Finna-list tukee attribuutteina kaikkia `userlistEmbed()`-PHP-helperin parametreja, ja välittää ne helperille. Lopputuloksen pitäisi olla identtinen PHP-helper-kutsun kanssa.


```html
<finna-list id="1"></finna-list>
```

<finna-list id="1" heading-level="4"></finna-list>
