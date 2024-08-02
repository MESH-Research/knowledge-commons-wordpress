import React from 'react';
import {NavLink} from "react-router-dom";

//import mlaLogo from '../styles/images/unicorns.jpg'; //Todo: img path needs to be changed to MLA Logo proper.
const Footer = () => (
    <footer>
        <div className={'row'}>
            <nav role="navigation" arialabel="Footer navigation" className={'footer-nav col-lg-4 offset-lg-1 col-sm-12'}>
                <ul>
                    <li>
                        <NavLink
                            to='/keyword'
                            exact={true}>Keywords
                        </NavLink>
                    </li>
                    <li>
                        <NavLink
                            to='/curators'
                            exact={true}>Curators
                        </NavLink>
                    </li>
                    <li>
                        <NavLink
                            to='/about'
                            exact={true}>About
                        </NavLink>
                    </li>
                    <li>
                        <NavLink
                            to='/introduction'
                            exact={true}>Introduction
                        </NavLink>
                    </li>
                    <li>
                        <NavLink
                            to='/search'
                            exact={true}>Search
                        </NavLink>
                    </li>
                </ul>
            </nav>
            <div className={'footer-branding col-lg-3 col-sm-12'}>
              Digital Pedagogy in the Humanities <br />
              <small>Published 2020</small>
              <br /><br />
              <a rel={"license"} href={"http://creativecommons.org/licenses/by-nc/4.0/"}>
                <img alt={"Creative Commons License"} style={{borderWidth: 0}} src={"https://i.creativecommons.org/l/by-nc/4.0/80x15.png"} />
              </a>
                <p style={{fontSize: 1.2 + 'rem'}}>This work is licensed under a <a rel={"license"} style={{color: '#ffffff', textDecoration: 'underline'}} href={"http://creativecommons.org/licenses/by-nc/4.0/"}>Creative Commons Attribution-NonCommercial 4.0 International License</a>.</p>
            </div>
            <div className={'footer-branding col-lg-3 offset-lg-1 col-sm-12'}>
              <a href={"https://www.mla.org"}><img src='/app/themes/digital-pedagogy/src/styles/images/MLA-logo-full-w.png' alt={'Modern Language Association'}/></a>
              <a href={"https://www.hcommons.org"}><img src='/app/themes/digital-pedagogy/src/styles/images/hc-logo.png' alt={"Humanities Commons"}/></a>
            </div>
            {/* Must require(imagePath) any in-code img src */}
        </div>
    </footer>
);
export default Footer;
