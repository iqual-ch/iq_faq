# iq_faq


FAQ module for pagedesigner.


## Background
Setting up FAQ pages is very time-consuming, especially if they have to comply
with schma.org standards. Another problem is the fact that we often have
different pages showing the same question, which leads to unnecessary
redundancies.

The purpose of this module is to solve both problems by
1. Introducing an FAQ pattern that automatically adds the question and answer
   to the schema.org metatags
2. Introducing an FAQ node type multiple several view blocks available in
   Pagedesigner:
	- **Manual selection**: Manually choose Questions (todo: multiple choice)
	- **Manual topic selection**: Manually choose topics as filter criteria
	- **Automatic topic selection**: Use the page's topics as fiter criteria

## Setup & Installation
Install module

    composer require iqual/iq_faq
    drush en iq_faq

Compile CSS

    drush iq_barrio_helper:sass-compile

If needed:
- Add iq_topics taxonomy field to content types to enable
  automatic topic selection.
- Add FAQ as filterable content type in content view


## Expected outcome

After the installation there should be:

- Pattern *iq_faq - FAQ Item* available as pagedesigner component
- New content type FAQ
- 3 FAQ View blocks available as pagedesigner components

Whenever an FAQ pattern is rendered on a page, its content (question & answer)
should be added to the page's metadata.
