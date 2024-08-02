import {ApolloClient} from 'apollo-client';
import {InMemoryCache} from "apollo-cache-inmemory";
import {ApolloLink} from 'apollo-link';
import {createHttpLink} from "apollo-link-http";
import {onError} from "apollo-link-error";
import {setContext} from 'apollo-link-context';
import gql from 'graphql-tag';
import {Agent} from 'https';

let nonce = (window.dpVars && window.dpVars.nonce) ? window.dpVars.nonce : null;
let endpoint = (window.dpVars && window.dpVars.graphqlEndpoint) ? window.dpVars.graphqlEndpoint : window.location.origin + "/graphql";

const linkError = onError(
  ({response, operation, graphQLErrors, networkError}) => {

    if (graphQLErrors) {
      console.log(response);
      console.log(operation);
      console.log(graphQLErrors);
      graphQLErrors.map(({debugMessage, locations, path}) =>
        console.log(
          `[GraphQL error]: 
                    Message: ${debugMessage}, 
                    Operation: ${operation.operationName}, 
                    Location: ${JSON.stringify(locations)},
                    Path: ${JSON.stringify(path)}`
        )
      );
    }

    if (networkError) console.log(`[Network error]: ${networkError}`);

    if (response) {
      response.errors = null;
    }
  });

const headerLink = setContext((operation, previousContext) => {
  const {headers} = previousContext;
  return {
    ...previousContext,
    headers: {
      ...headers,
      credentials: 'same-origin',
      'Content-Type': 'application/json',
      'X-WP-Nonce': nonce
    }
  }
});
const cache = new InMemoryCache().restore(window.__APOLLO_STATE__);

const httpLink = createHttpLink({
  uri: endpoint,
  fetchOptions: {
    agent: new Agent({rejectUnauthorized: false}),
  },
});
const defaultOptions = {
  watchQuery: {
    fetchPolicy: 'cache-first',
    errorPolicy: 'ignore',
  },
  query: {
    fetchPolicy: 'cache-first',
    errorPolicy: 'all',
  },
};
export default new ApolloClient({
  // ssrForceFetchDelay: 100,
  link: ApolloLink.from([
    linkError,
    headerLink,
    httpLink,
  ]),

  // here we're initializing the cache with the data from the server's cache
  cache,
  connectToDevTools: true, defaultOptions: defaultOptions,
  resolvers: {
    Mutation: {
      updateCacheState: (_, {isDragging, refreshDrawer, refreshCollectionPageData, refreshKeywordPageData}, object) => {
        const c = object.cache;
        const data = c.readQuery({
          query: gql`
                        {
                            appState @client {
                                isDragging,
                                refreshDrawer,
                                refreshCollectionPageData,
                                refreshKeywordPageData
                            }
                        }
                    `,
        });

        const {appState} = data;
        let newAppState = {
          __typename: `AppState`,
        };
        if (isDragging) {
          newAppState.isDragging = isDragging
        }
        if (refreshDrawer) {
          newAppState.refreshDrawer = refreshDrawer
        }
        if (refreshCollectionPageData) {
          newAppState.refreshCollectionPageData = refreshCollectionPageData
        }
        if (refreshKeywordPageData) {
          newAppState.refreshKeywordPageData = refreshKeywordPageData
        }

        newAppState = {...appState, ...newAppState};
        c.writeData({data: {appState: newAppState}});
        return null
      },
    },
  },
});

cache.writeData({
  data: {
    appState: {
      isDragging: "no",
      refreshDrawer: "no",
      refreshCollectionPageData: "no",
      refreshKeywordPageData: "no",
      __typename: "AppState",
    },
  },
});
