# APM Publication API

This repo provides the official definition of APM's publication API in PHP and a client implementation. 

> This repo is still **alpha**. DO NOT assume any information or code here will stay as it is right now.

## API Description

The Publication API is how APM publishes its data to external clients, most notably APE. APM users determine which
resources they want to make available to specific clients, and the clients call the API to get a listing of these
resources and to get the data.

There are two calls:

- `api/publication/list`: returns a StandardApiResponse with an array of PublicationListing objects
- `api/publication/{id}/get`: returns a StandardApiResponse with the data for the given id.

A publication listing consists of general information about a publication:

- type: for example, `'Transcription'`
- id
- versionTimeString
- title
- description

The data for a publication contains this same information together with all required resource's data according to the
publication type.

## Publication Types

### Text

A text string without any formatting.

### Transcription

The transcription of a document, normally a manuscript. It consists of an array of pages, each one with a number of
columns, with each column containing a transcription. A column transcription consists of an array of elements (main
text, marginal additions, etc.). Each element in turn consists of an array of transcription items: simple text,
abbreviations, additions, etc.

### Edition

*TBD*

