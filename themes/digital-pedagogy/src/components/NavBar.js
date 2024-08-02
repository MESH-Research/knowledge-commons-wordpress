import React from 'react';
import {NavLink} from "react-router-dom";
import {MdSearch} from 'react-icons/md';

const NavBar = (props) => (
    <div className="container-fluid">
        <nav role="navigation" aria-label="Main navigation" className="navbar navbar-expand-lg navbar-dark">
          <img src='/app/themes/digital-pedagogy/src/styles/images/mla-logo-w.png' alt={'Modern Language Association'} style={{height: 35 + 'px'}} />

            {!props.isHomepage &&
            <div className="d-flex flex-nowrap w-100 site__title">
                <div className={'navbar-brand header__title'}>
                    <NavLink
                        className={'nav-link header__title--link'}
                        to='/'
                        exact={true}>Digital Pedagogy in the Humanities
                    </NavLink>
                </div>
                <button
                    className="navbar-toggler ml-auto"
                    type="button"
                    data-toggle="collapse"
                    data-target="#navbarCollapse"
                    aria-controls="navbarCollapse"
                    aria-expanded="false"
                    aria-label="Toggle navigation">
                    <span className="navbar-toggler-icon"/>
                </button>
            </div>}
            {props.isHomepage &&
              <button
                className="navbar-toggler ml-auto"
                type="button"
                data-toggle="collapse"
                data-target="#navbarCollapse"
                aria-controls="navbarCollapse"
                aria-expanded="false"
                aria-label="Toggle navigation">
                <span className="navbar-toggler-icon"/>
            </button>
            }

            <div className="collapse navbar-collapse" id="navbarCollapse">

                <ul className="navbar-nav ml-auto">
                    <li className="nav-item intro">
                        <NavLink
                            className={'nav-link'}
                            to='/introduction'
                            activeClassName={'header__nav--Selected'}
                            exact={true}>Introduction
                        </NavLink>
                    </li>
                    <li className="nav-item">
                        <NavLink
                            className={'nav-link'}
                            to='/keyword'
                            activeClassName={'header__nav--Selected'}
                            exact={true}>Keywords
                        </NavLink>
                    </li>
                    <li className="nav-item">
                        <NavLink
                            className={'nav-link'}
                            to='/about'
                            activeClassName={'header__nav--Selected'}
                            exact={true}>About
                        </NavLink>
                    </li>
                    <li className="nav-item">
                        <NavLink
                            className={'nav-link'}
                            to='/help'
                            activeClassName={'header__nav--Selected'}
                            exact={true}>Help
                        </NavLink>
                    </li>
                    {
                        window.dpVars.userId == false ?
                            <li className="nav-item dropdown">
                                <a
                                    className={'nav-link'}
                                    href={"/login"}>Log in
                                </a>
                            </li> : ""
                    }
                    {
                        window.dpVars.userId == false ?
                            <li className="nav-item dropdown">
                                <a
                                    className={'nav-link'}
                                    href={"/register"}>Sign Up
                                </a>
                            </li> : ""
                    }
                    {
                        window.dpVars.userId != false ?
                            <li className="nav-item dropdown">
                                <a
                                    className={'nav-link'}
                                    href={"/wp-login.php?action=logout"}>Log Out
                                </a>
                            </li> : ""
                    }
                    <li className="nav-item">
                        <NavLink
                            className={'nav-link header__nav__search'}
                            to='/search'
                            activeClassName={'header__nav--Selected'}
                            exact={true}
                            aria-label="Search">
                            {<MdSearch/>}
                        </NavLink>
                    </li>
                </ul>
            </div>
        </nav>
    </div>

);
export default NavBar;
