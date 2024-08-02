const fetch = require('node-fetch');
const fs = require('fs');
const settings = require('../../env');
fetch(`${settings.env.GRAPHQL_ENDPOINT}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        query: `
      {
        __schema {
          types {
            kind
            name
            possibleTypes {
              name
            }
          }
        }
      }
    `,
    }),
})
    .then(result => result.json())
    .then(result => {
        // here we're filtering out any type information unrelated to unions or interfaces
        const filteredData = result.data.__schema.types.filter(
            type => type.possibleTypes !== null,
        );
        result.data.__schema.types = filteredData;
        fs.writeFile(`${settings.env.FRAGMENT_TYPE_JSON}`, JSON.stringify(result.data), err => {
            if (err) console.error('Error writing IDE fragmentTypes file', err);
            console.log('IDE Fragment types successfully extracted!');
        });
    });