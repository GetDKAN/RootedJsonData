# RootedJsonData

[![GetDKAN](https://circleci.com/gh/GetDKAN/RootedJsonData.svg?style=svg)](https://app.circleci.com/pipelines/github/GetDKAN/RootedJsonData?branch=master)

Access and modify JSON-based data objects while enforcing JSON Schema.

This library primarily wires together [JsonPath-PHP](https://github.com/Galbar/JsonPath-PHP/) and [Opis JSON Schema](https://github.com/opis/json-schema), providing a JSON Object class that functions like a small internal service. Data can be added, retrieved and modified through a simple API, and any changes will immediately provoke a re-validation. Validation errors through exceptions with actionable messages.
