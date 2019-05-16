**Ce document a �t� traduit automatiquement. Veuillez noter qu'il peut y avoir des erreurs ou des correspondances non exactes avec le libell� utilis� dans le backend.**

# Nouvelles avec images: Un nouveau module de nouvelles pour le CMS WBCE
Actualit�s avec images (en abr�g�: NWI) facilite la cr�ation de pages de nouvelles ou de publications.
Il est bas� sur "l'ancien" module d'actualit�s (3.5.12), mais a �t� �tendu de plusieurs fonctions:
- Poster une photo
- galerie d'images int�gr�e (ma�onnerie ou Fotorama)
- 2e zone de contenu facultative
- Trier les articles avec drag & drop
- D�placement / copie de publications entre groupes et sections
- Importation de sujets et d'actualit�s "classiques"

La fonction de commentaire rudimentaire et peu s�r de l'ancien module de nouvelles a �t� abandonn�e; Si n�cessaire, cette fonction peut �tre int�gr�e aux modules appropri�s (Global Comments / Easy Comments ou Reviews).

## T�l�charger
Le module est un module de base � partir de WBCE CMS 1.4 et install� par d�faut. De plus, le t�l�chargement est disponible dans le [R�f�rentiel de modules compl�mentaires WBCE CMS] (https://addons.wbce.org).

## Licence
NWI est sous la [Licence publique g�n�rale GNU v3.0] (http://www.gnu.org/licenses/gpl-3.0.html).

## Configuration requise
NWI ne n�cessite aucune configuration syst�me particuli�re et fonctionne �galement avec les anciennes versions de WBCE ainsi que WebsiteBaker.


## installation
1. Si n�cessaire, t�l�chargez la derni�re version de [AOR] (https://addons.wbce.org)
2. Comme tout autre module WBCE via les extensions & gt; Installer des modules

## Utilisation

### Mise en route et �criture
1. Cr�ez une nouvelle page avec "News with Images"
2. Cliquez sur "Ajouter un message"
3. Remplissez le titre et, si n�cessaire, d'autres champs, s�lectionnez �ventuellement des images. La fonction des champs de saisie est probablement explicite.
4. Cliquez sur "Enregistrer" ou "Enregistrer et revenir en arri�re"
5. R�p�tez les �tapes 1. - 4. � quelques reprises et regardez le tout dans le frontal

En principe, NWI peut �tre combin� avec d'autres modules sur une page ou dans un bloc, mais il peut ensuite, comme tout module g�n�rant ses propres pages de d�tail, aboutir � des r�sultats qui ne r�pondent pas aux attentes / attentes.

### images dans le post
Une image d'aper�u peut �tre t�l�charg�e pour chaque article. Elle est affich�e sur la page d'aper�u et, si n�cessaire, sur la page d'envoi. En outre, il est possible d�ajouter un nombre quelconque d�images � une publication, qui sont affich�es sous forme de galerie d�images. La pr�sentation de la galerie est pr�sent�e sous forme de galerie Fotorama (vignettes, image en largeur) ou de galerie de ma�onnerie (mosa�que).

Le script de galerie utilis� est d�fini pour toutes les publications dans les param�tres de chaque section.

Les images de la galerie sont t�l�charg�es au fur et � mesure que la publication est enregistr�e, et peuvent ensuite �tre sous-titr�es, utilis�es ou supprim�es.

Lors du t�l�chargement de fichiers portant le m�me nom que des images existantes, les fichiers existants ne sont pas �cras�s, mais les fichiers suivants sont compl�t�s par une num�rotation cons�cutive (bild.jpg, bild_1.jpg, etc.).

La gestion des images ne se fait que sur la page de publication, pas sur l'administration des m�dias de WBCE, car NWI ne "sait" pas sinon, � quelle image appartiennent ou sont manquantes, etc.

### Groupes
Les messages peuvent �tre affect�s � des groupes. Cela a d'une part une influence sur l'ordre (les articles sont tri�s en fonction du groupe, puis en fonction d'un crit�re suppl�mentaire � sp�cifier), et d'autre part, il est possible de g�n�rer des pages de synth�se par sujet. Celles-ci sont ensuite accessibles via l�URL de la page NWI avec le param�tre g? = GROUP_ID, par ex. news.php? g = 2.

Une publication ne peut �tre attribu�e qu'� un seul groupe.

Un ou plusieurs messages peuvent �tre copi�s et d�plac�s entre les groupes.

### fonction d'importation
Tant qu'aucune publication n'a �t� publi�e dans la section NWI respective, les publications du module d'actualit�s classique, les autres sections de NWI ainsi que les sujets peuvent �tre import�s automatiquement.
Les param�tres de page de la page source sont appliqu�s. Toutefois, lors de l�importation de publications Sujets, une retouche manuelle est toujours n�cessaire si la fonction "Images suppl�mentaires" a �t� utilis�e dans Sujets.

### Copier / d�placer des messages
� partir de l�aper�u des publications dans le backend, vous pouvez copier les publications individuelles, les publications s�lectionn�es ou toutes (marqu�es) d�une section, ou les copier ou les d�placer d�une section � une autre (m�me sur des pages diff�rentes). Les publications copi�es sont toujours initialement invisibles dans le frontal (s�lection active: "non").

### Supprimer les messages
Vous pouvez supprimer un, plusieurs ou tous les articles (s�lectionn�s) de l'aper�u des articles. Apr�s confirmation, les messages respectifs sont irr�vocables ** DETRUITS **, il n'y a ** pas ** de moyen de les restaurer!

## configuration
Tous les r�glages, sauf si un deuxi�me bloc doit �tre utilis� ou non, peuvent �tre effectu�s via le backend dans les param�tres du module (accessibles via le bouton "Options").

### page d'aper�u
- ** Classer par **: d�finition de l'ordre des publications (personnalis� = d�finition manuelle, les publications apparaissent telles qu'elles sont dispos�es dans le backend, date de d�but / date d'expiration / soumises (= date de cr�ation) / ID de soumission: chaque ordre d�croissant selon au crit�re correspondant)
- ** Messages par page **: s�lection du nombre d'entr�es (image / texte teaser) par page � afficher
- ** header, post loop, footer **: code HTML pour formater la sortie
- ** Redimensionner l'image d'aper�u � ** Largeur / hauteur de l'image en pixels. ** aucun ** recalcul automatique n'aura lieu si des modifications sont apport�es, il est donc logique de penser � l'avance
� propos de la taille souhait�e et puis ne changez pas la valeur � nouveau.

Espaces r�serv�s autoris�s:
#### En-t�te / Pied de page
- [NEXT_PAGE_LINK] "Page suivante", li�e � la page suivante (si la page de pr�sentation est divis�e en plusieurs pages),
- [NEXT_LINK], "Next", s.o.,
- [PREVIOUS_PAGE_LINK], "Page pr�c�dente", s.o.,
- [PREVIOUS_LINK], "Pr�c�dent", s.o.,
- [OUT_OF], [OF], "x de y",
- [DISPLAY_PREVIOUS_NEXT_LINKS] "hidden" / "visible", selon que la pagination est requise ou non
#### post loop
- [PAGE_TITLE] titre de la page,
- [GROUP_ID] ID du groupe auquel la publication est affect�e, pour les publications sans groupe "0"
- [GROUP_TITLE] Titre du groupe auquel le poste est affect�, pour les postes sans groupe "",
- [GROUP_IMAGE] Image (& lt; img src ... / & gt;) du groupe auquel le poste est affect� pour les postes sans groupe "",
- [DISPLAY_GROUP] * h�riter * ou * aucun *,
- [DISPLAY_IMAGE] * h�riter * ou * aucun *,
- [TITRE] titre (titre) de l'article,
- [IMAGE] post image (& lt; img src = ... / & gt;),
- [SHORT] court texte,
- [LINK] Lien vers la vue d�taill�e de l'article,
- [MODI_DATE] date du dernier changement de post,
- [MODI_TIME] Heure (heure) du dernier changement de poste,
- [CREATED_DATE] Date de cr�ation de la publication,
- [CREATED_TIME] heure � laquelle le poste a �t� cr��,
- date de d�but [PUBLISHED_DATE],
- [PUBLISHED_TIME] heure de d�but,
- [USER_ID] ID du cr�ateur de la publication,
- [USERNAME] nom d'utilisateur du cr�ateur de la publication,
- [DISPLAY_NAME] Nom complet du cr�ateur de la publication,
- [EMAIL] Adresse email du cr�ateur de la publication,
- [TEXT_READ_MORE] "Afficher les d�tails",
- [SHOW_READ_MORE], * cach� * ou * visible *,
- [GROUP_IMAGE_URL] URL de l'image du groupe

### post view
- ** En-t�te de message, Contenu, Pied de page, Bloc 2 **: Code HTML pour formater le message.

Espaces r�serv�s autoris�s:
#### En-t�te de message, pied de message, bloc 2
- [PAGE_TITLE] titre de la page,
- [GROUP_ID] ID du groupe auquel la publication est affect�e, pour les publications sans groupe "0"
- [GROUP_TITLE] Titre du groupe auquel le poste est affect�, pour les postes sans groupe "",
- [GROUP_IMAGE] Image (& lt; img src ... / & gt;) du groupe auquel le poste est affect� pour les postes sans groupe "",
- [DISPLAY_GROUP] * h�riter * ou * aucun *,
- [DISPLAY_IMAGE] * h�riter * ou * aucun *,
- [TITRE] titre (titre) de l'article,
- [IMAGE] post image (& lt; img src = ... / & gt;),
- [SHORT] court texte,
- [MODI_DATE] date du dernier changement de post,
- [MODI_TIME] Heure (heure) du dernier changement de poste,
- [CREATED_DATE] Date de cr�ation de la publication,
- [CREATED_TIME] heure � laquelle le poste a �t� cr��,
- date de d�but [PUBLISHED_DATE],
- [PUBLISHED_TIME] heure de d�but,
- [USER_ID] ID du cr�ateur de la publication,
- [USERNAME] nom d'utilisateur du cr�ateur de la publication,
- [DISPLAY_NAME] Nom complet du cr�ateur de la publication,
- [EMAIL] Adresse email du cr�ateur de la publication

#### contenu de nouvelles
- [CONTENU] Contenu du message (HTML)
- [IMAGES] Images / Galerie HTML

### Param�tres Galerie / Image
- ** Galerie d'images **: S�lection du script de galerie � utiliser. Veuillez noter que toute personnalisation apport�e au code de la galerie dans le champ Contenu du message sera perdue en cas de modification.
- ** Boucle d'image **: le code HTML pour la repr�sentation d'une seule image doit correspondre au script de galerie correspondant.
- ** Max. Taille de l'image en octets **: Taille de fichier par fichier image, pourquoi cela doit maintenant �tre sp�cifi� en octets et non pas en Ko lisible ou Mo, je ne sais tout simplement pas
- ** Redimensionner les images de la galerie � / Taille de la vignette largeur x hauteur **: exactement pareil. ** Aucun ** recalcul automatique n'aura lieu si des modifications sont apport�es. Il est donc logique de penser � l'avance � la taille souhait�e et de ne pas modifier � nouveau la valeur.
- ** Recadrage **: Voir l'explication � la page.

### 2e bloc
Un deuxi�me bloc peut �ventuellement �tre affich� si le mod�le le prend en charge.
- Utiliser le bloc 2 (par d�faut): Aucune entr�e ou entr�e * define ('NWI_USE_SECOND_BLOCK', true); * dans le fichier config.php dans la racine
- ne pas utiliser le bloc 2: entr�e * define ('NWI_USE_SECOND_BLOCK', false); * dans le fichier config.php dans la racine