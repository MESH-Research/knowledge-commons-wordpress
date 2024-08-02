import 'babel-polyfill';
import React from 'react';
import ReactDom from 'react-dom'
import AppRouter from './routers/AppRouter';
import { ApolloProvider } from 'react-apollo';
import client from "./graphql/bin/client"
//Styles

import 'popper.js/dist/umd/popper';
import 'bootstrap';
import './styles/styles.scss';



ReactDom.render(
    <ApolloProvider client={client}>
        <AppRouter />
    </ApolloProvider>,
    document.getElementById('root')
);