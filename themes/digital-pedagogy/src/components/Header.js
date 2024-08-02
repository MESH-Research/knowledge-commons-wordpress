import React from 'react';
import CollectionDrawer from './CollectionsDrawer'
import NavBar from "./NavBar";
import {withRouter, useLocation} from 'react-router-dom';
const Drawer = withRouter(CollectionDrawer);

//Global app header
class Header extends React.Component {
    constructor(props) {
        super(props);
    }

    componentDidMount() {
       document.title = "Digital Pedagogy"
    }

    render() {
        return (

            <div className={'sticky-top'} role="banner">

                <a href={'#main-content'} className={'sr-only sr-only-focusable btn-secondary p-3 d-inline-block m-3'}>Skip to main content</a>

                <header className={'header'}>
              
                    {/*
                        Nav bar has different logic for homepage vs regular pages
                    */}
                    <NavBar/>
                </header>
                <Drawer/>
            </div>
        );
    }
}

export default Header;
