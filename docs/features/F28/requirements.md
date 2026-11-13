# F28 Button icons: requirements

Status: gates 1-2 written 2026-11-13 at the user's request (*"for the buttons, we should have a icon picker.
currently we had to use hardcoded html like this `<span class="amazon-icon"></span>` for icons"*). The user chose
the icon source and the handling of broken icons the same day (section 5). **Gate 4 given by the user 2026-11-13** ("Approve, start 14F")
for Tasks 14F-14G. Register item **U11** (an addition: Lasso has no picker).

## 1. User story and acceptance criteria

- As an editor, I choose a button's or badge's icon from a list instead of typing HTML, so an icon is never misspelt
  and I can add a new one without editing CSS.

- **AC1** Settings → Display lists the site's icons, each with its picture and name: the six the site's CSS defines
  (Amazon, Pricetag, Medal, Full review, Alsogood, Youtube).
- **AC2** Adding an icon there (a name and an image from the media library) makes it appear in every icon picker, and
  a box text that uses it shows the image on the site.
- **AC3** A Button Text field shows a picker left of the text; choosing an icon and typing "Buy Now" stores
  `<span class="amazon-icon"></span> Buy Now`, exactly the form stored today.
- **AC4** A stored text whose class is not in the set shows "Unknown icon: madel-icon" on the picker, and is stored
  unchanged unless the editor picks another icon.
- **AC5** "No icon" stores the text alone.
- **AC6** Without JavaScript the field is today's plain text field and still saves.

## 2. Evidence

- **Lasso has no icon feature.** Its source (`D:\lasso\lasso`) never names `amazon-icon`, `pricetag-icon` or
  `medal-icon`; its fields are plain text and the box prints them as HTML (Task 6 decision 5 keeps
  `<span class="…">` for that reason).
- **The icons are the site's own CSS**: the child theme's Additional CSS (`custom_css` post 5977,
  `generatepress_child`), between `/*edit icons here*/` and `/*edit icons ends here*/`. Each rule is
  `span.<name>-icon { content: url('/wp-content/uploads/…svg'); object-fit: contain; height/width …; position:
  relative; top …; @media (max-width: 768px) { smaller } }`. Six rules: `pricetag-icon` (25×21), `medal-icon`
  (0.9em), `full-review-icon`, `amazon-icon`, `alsogood-icon`, `youtube-icon` (21×21). The SVGs are media library
  attachments (e.g. post 9741, `pricetag-icon`).
- **Live texts** (this copy, 2026-11-13), always `<span class="…"></span>` then a space and the text:

  | Field | With an icon | Classes |
  |---|---|---|
  | Primary button text (230) | 230 | amazon 226, pricetag 2, medal 1, **underarmour 1** |
  | Secondary button text (183) | 177 | pricetag 127, amazon 50 |
  | Badge text (198) | 158 | medal 140, alsogood 1, **madel 8, alsogreat 5, `alsogood` 2, `tick mark-icon` 1**, one other |
  | Display settings | 6 | amazon (primary), pricetag (secondary), on all three types |

  **17 texts name a class the CSS does not define**, so readers see no icon on them today.
- The `alsogood-icon` rule's URL has no leading slash (`url('wp-content/…')`), so it resolves only on the home page.
  Recorded, not changed (the site's CSS is the user's).

## 3. Behaviours

- **B1 The icon set** is Affibloom's list: name, class, image. The six from the site's CSS are imported once, the first
  time the set is read: the class and the image URL from each `span.<class> { content: url(…) }` rule, the name from the
  class ("full-review-icon" → "Full review"). Imported icons keep their CSS where it is; Affibloom prints none for them,
  so boxes keep their exact look (ADR-6).
- **B2 Adding an icon** in Settings → Display: a name (1-40 characters) and an image (SVG, PNG or WebP) chosen from the
  media library. Its class is the name as a slug plus `-icon`, unique. Affibloom prints CSS for it on the site and in
  the admin, shaped like the site's own rules (21×21, 3px right margin, raised 2px; 15×15 at 768px and below).
- **B3 Removing an icon** asks first ("Remove <name>? Texts that use it will show no icon until another is chosen.").
  Only icons added in Affibloom can be removed; an imported one is the site's CSS and says so.
- **B4 The picker** is a button at the left of a text field: the current icon's picture, a neutral glyph for none, or
  a warning glyph for an unknown class. It opens a menu: "No icon", then every icon (picture and name), the current one
  ticked; arrow keys, Enter and Escape work, focus returns to the button.
- **B5 Stored form is unchanged:** `<span class="<class>"></span> <text>`, or the text alone. A stored text is split
  only when it is exactly one leading empty span with a class and plain text after it; anything else (other HTML)
  keeps today's plain field.
- **B6 Where:** the two button texts on Settings → Display (Task 10A, today), the product page's two Button Text
  fields (14A, today) and Badge Text (14B), and quick edit (Task 16).

## 4. Out of scope

- Changing the site's Additional CSS or the stored texts (section 5, D2).
- Icons anywhere but button and badge texts (Lasso has no other place for them).

## 5. Decisions

- **D1 (the user, 2026-11-13): Affibloom keeps an icon set**, managed in Settings → Display, with the site's six
  imported and their CSS left in place; a new icon needs no CSS.
- **D2 (the user, 2026-11-13): broken classes are flagged, not fixed.** The picker shows "Unknown icon: <class>"; the
  data and the boxes stay as they are. The migration (Task 26) copies the texts unchanged.
- **D3 (Claude): the picker enhances the existing field.** The posted value is still the field's text (composed by the
  script), so every cleaner, route and test that reads it today is unchanged (Task 6 decision 5).
