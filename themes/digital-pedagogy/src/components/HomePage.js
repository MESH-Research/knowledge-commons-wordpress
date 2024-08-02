import React from 'react';
import NavBar from "./NavBar";
import KeywordsPage from '../components/KeywordsPage';
import {NavLink, link, Link, withRouter} from "react-router-dom";
import CollectionDrawer from './CollectionsDrawer'
const Drawer = withRouter(CollectionDrawer);

// stateless function rather then class approach.

const HomePage = () => (
    <>
        <div className={'homepageBody'}>
            <div className="container-fluid homepageBody__wrapper m-0 p-0">
                <div className="homepageBody__intro">
                    <div className="row homepageBody__intro__outer-wrapper">
                        <div className="col homepageBody__intro__nav">
                        <a href={'#main-content'} className={'sr-only sr-only-focusable btn-secondary p-3 d-inline-block m-3'}>Skip to main content</a>

                      
                            <header className={'homepageBody__intro__nav__header col-12'}>

                                <NavBar isHomepage={true}/>
                            </header>
                            <Drawer/>
                        </div>
                        <div className="row" role="main" id="main-content">
                            <div className="col-md-10 col-lg-8 offset-md-1 offset-lg-2 homepageBody__intro__inner-wrapper">
                                <div className="row homepageBody__intro__inner-wrapper__text__wrapper">
                                    <div className="row homepageBody__intro__inner-wrapper__title">
                                        <div
                                            className="col-12 homepageBody__intro__inner-wrapper__title__container text-center">
                                            <h1 className="homepageBody__intro__inner-wrapper__title__container__h1">Digital Pedagogy in the Humanities: Concepts, Models, and Experiments</h1>
                                        </div>
                                        <div
                                            className="col-12 homepageBody__intro__inner-wrapper__subtitle__container text-center">
                                            <h2 className={'homepageBody__intro__inner-wrapper__subtitle__container__h2'}>A peer-reviewed, scholarly collection of pedagogical artifacts.</h2>
                                        </div>
                                    </div>
                                    <div className="row homepageBody__intro__inner-wrapper__text">
                                        <div
                                            className="col-md-8 homepageBody__intro__inner-wrapper__text__copy__container">

                                            <em>Digital Pedagogy in the Humanities</em> is a peer-reviewed, curated collection of reusable and remixable resources for teaching and research.
                                            Organized by keyword, the annotated artifacts can be saved in collections for future reference or sharing.
                                            Each keyword includes a curatorial statement and artifacts that exemplify that keyword.
                                            You can read the keywords comprehensively, as you would a printed collection, and browse artifacts,
                                            exploring certain types or subject matter. For other ideas about using this collection,
                                            see the introduction, <em><a href={"/introduction"}>Curating Digital Pedagogy in the Humanities</a></em>.


                                        </div>
                                        <div
                                            className="col-md-4 homepageBody__intro__inner-wrapper__text__editors__container">
                                            <h3 className={'homepageBody__intro__inner-wrapper__text__editors__container__h3'}>Editors: </h3>
                                            <ul className={'homepageBody__intro__inner-wrapper__text__editors__container__ul'}>
                                                <li><a href="https://hcommons.org/members/frostdavis/">Rebecca Frost Davis</a>, St. Edward’s University</li>
                                                <li><a href="https://hcommons.org/members/mkgold/">Matthew K. Gold</a>, Graduate Center, City University of New York</li>
                                                <li><a href="https://hcommons.org/members/kdharris/">Katherine D. Harris</a>, San José State University</li>
                                                <li><a href="https://hcommons.org/members/jentery/">Jentery Sayers</a>, University of Victoria</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div className="row homepageBody__intro__inner-wrapper__call-to-action">
                                    <div className="col-12 homepageBody__intro__inner-wrapper__call-to-action text-center  middle-center">
                                        <div className="arrow-down"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div className="row homepageBody__intro__outer-wrapper__call-to-action">
                        <div className="col-lg-8 offset-lg-2 col-md-10 offset-md-1 homepageBody__intro__outer-wrapper__call-to-action__text text-center">
                          {window.dpVars.userId == false ?
                            <><a href={"/login"}>Log in</a> or <a href={"/register"}>sign up</a> to create your first collection.</> :
                            <>Browse <Link to="/keyword">keywords</Link> or <Link to="/search">search</Link> for artifacts.</> }
                        </div>
                    </div>
                    <div className="row homepageBody__keywords__outer-wrapper">
                        <h2 className={'homepageBody__keywords__title text-center w-100'}>Explore Keywords</h2>
                        <div className="col homepageBody__keywords__inner-wrapper">
                            <KeywordsPage fullWidth={true} hideTitle={true}/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </>
);
export default HomePage;
