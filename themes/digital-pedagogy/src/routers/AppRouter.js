import React from 'react';
import {BrowserRouter, Route, Switch} from 'react-router-dom';
import HomePage from '../components/HomePage';
import Page from '../components/Page';

import KeywordsPage from '../components/KeywordsPage';
import KeywordPage from '../components/KeywordPage';
import NotFound from '../components/NotFound';
import SearchPage from '../components/SearchPage';
import SearchResultsPage from '../components/SearchResultsPage';

import Header from '../components/Header';
import Footer from '../components/Footer';

const AppRouter = () => (
    <BrowserRouter>
        <div>
            <Switch>
                <Route path="/" component={HomePage} exact={true}/>
                <>
                    <Header/>
                    <div className={'min-px-h-500 container-fluid'}>
                        <Switch>
                            <Route path="/404/:term" exact={true}
                                   render={(props) => <NotFound {...props}/>}/>
                            <Route path="/404" exact render={(props) => <NotFound {...props}/>}/>
                            <Route path="/404/" exact render={(props) => <NotFound {...props}/>}/>
                            <Route path="/keyword" component={KeywordsPage} exact={true}/>
                            <Route path="/search" component={SearchPage} exact={true}/>
                            <Route path="/search/type/:term" exact={true}
                                   render={(props) => <SearchResultsPage {...props} key={Math.floor(Date.now() / 1000)}
                                                                         isKeywordPage={false} search_type="type"/>}/>
                            <Route path="/search/tag/:term" exact={true}
                                   render={(props) => <SearchResultsPage {...props} key={Math.floor(Date.now() / 1000)}
                                                                         isKeywordPage={false} search_type="tag"/>}/>
                            <Route path="/search/:term"
                                   render={(props) => <SearchResultsPage {...props} key={Math.floor(Date.now() / 1000)}
                                                                         isKeywordPage={false} search_type="search"/>}/>
                            <Route path="/keyword/:id"
                                   render={(props) => <KeywordPage {...props} key={Math.floor(Date.now() / 1000)}
                                                                   isCollection={false} isKeywordPage={true}/>}/>
                            <Route path="/collection/:id"
                                   render={(props) => <KeywordPage {...props} key={Math.floor(Date.now() / 1000)}
                                                                   isCollection={true} isKeywordPage={false}/>}/>
                            <Route path="/:parent/:id"
                                   render={(props) => <Page {...props} key={Math.floor(Date.now() / 1000)} /> } />
                            <Route path="/:id"
                                   render={(props) => <Page {...props} key={Math.floor(Date.now() / 1000)} /> } />
                            <Route component={NotFound}/>
                        </Switch>
                    </div>
                </>
            </Switch>
            <Footer/>
        </div>
    </BrowserRouter>
);

export default AppRouter;
