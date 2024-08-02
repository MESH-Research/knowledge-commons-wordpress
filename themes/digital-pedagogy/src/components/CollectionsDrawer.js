import React from 'react';
import ReactDrawer from 'react-drawer'; //the 3rd party drawer component
import {MdAddCircleOutline, MdArrowDropDown, MdArrowDropUp} from 'react-icons/md'; //icons
import CollectionCard from "./CollectionCard"; //collection card component
import uuid from "uuid/v4"; // unique ID
import {withApollo, Query} from 'react-apollo'; //may not be needed.
// Todo: test class without the WithApollo wrapper.
import {q_collectionList, setCacheState} from "../graphql/bin/queries"; //list collections and store var into cache state.
import readCache from "../graphql/queries/Cache_ReadState.graphql"; //the GraphQL query for use with Query component.

class CollectionsDrawer extends React.Component {
    constructor(props) {
        super(props); // inherit the parent constructor
        this.state = { // default state
            dataLoaded: false,
            collections: this.getData(),
            open: false,
            position: 'top',
            noOverlay: true,
            refreshCards: false
        };

        // we need to bind the class functions to ensure they have access to the 'this' variable
        this.toggleDrawer = this.toggleDrawer.bind(this);
        this.onDrawerClose = this.onDrawerClose.bind(this);
        this.setPosition = this.setPosition.bind(this);
        this.setNoOverlay = this.setNoOverlay.bind(this);
        this.getData = this.getData.bind(this);
    }

    /*
        The getData method pulls in the data from the associated queries, formats (where applicable) and writes data to state.
        This async version of getData will wait for the data to be processed before passing result.
    */
    async getData() {
        return await q_collectionList(window.dpVars.userId).then(async function (results) {
            return (await this.setState({collections: results.data.collections.edges, dataLoaded: true}));
        }.bind(this)).catch(function (e) {
            console.log("Promise Rejected getData");
            console.log(e);
        });
    }

    // sets the refresh local state boolean to true.
    setRefresh = () => {
        this.setState({refreshCards: true});
    };

    // sets the position local state boolean to true.
    // may be obsolete.
    // Todo: test removal of setPosition function.
    setPosition(e) {
        this.setState({position: e.target.value});
    }

    // may be obsolete.
    // Todo: test removal of setNoOverlay function.
    setNoOverlay(e) {
        this.setState({noOverlay: e.target.checked});
    }

    // Toggles the drawer open and closed.
    toggleDrawer() {
        this.setState({open: !this.state.open});
    }

    //closes the drawer
    onDrawerClose() {
        this.setState({open: false});
    }

    //renders the drawer html including the exteernal "My Collections" button.
    render() {
        const button = ({isOpen}) => !isOpen ?
            <span>My Collections<MdArrowDropDown size={32}/></span> :
            <span>My Collections<MdArrowDropUp size={32}/></span>;
        const cards = ({isOpen}) => <>
            {/* <> is a pseudo wrapper div used by ReactJS wrap JSX instead of writing an extra <div> */}
            <div className="w-100 collection_button">
                <button
                    className={'collection_button__btn'}
                    style={{textAlign: 'center'}}
                    onClick={this.toggleDrawer}
                    disabled={this.state.open && !this.state.noOverlay}>
                    {button({isOpen})}
                </button>
            </div>
            <div className="collection_drawer">
                {/* The Query component is used in conjunction with the cache appState.
                    This is used to check if a cache state refresh has been setup
                    (as opposed to a local state refresh)
                 */}
                <Query query={readCache}>
                    {({loading, error, data}) => {
                        if (error) return <h1>Error...</h1>;
                        if (loading || !data) return <h1>Loading...</h1>;
                        if (data.appState.refreshDrawer === "yes") {
                            setCacheState({refreshDrawer: "no"}).then(() => {
                                this.setRefresh();
                            });
                        }

                        {/* this is the Query component return, not the render's return */
                        }
                        return (
                            <ReactDrawer
                                open={data.appState.isDragging === "yes" ? true : isOpen}
                                position={this.state.position}
                                onClose={this.onDrawerClose}
                                noOverlay={this.state.noOverlay}>
                                <i onClick={this.onDrawerClose} className="icono-cross"></i>
                                <div className='collection_drawer'>
                                    <div className='container'>
                                        <div className="row">
                                            <div className='content w-100 container collection_drawer__content'>
                                                <div className={`collection-card`}>
                                                    <div className={`row collection-card__wrapper`}>
                                                        {
                                                            window.dpVars.userId == false
                                                                ?
                                                                <div
                                                                    className={`row justify-content-center text-center not-logged-in-message`}>
                                                                    <div className={'col-auto middle-center'}>
                                                                        <MdAddCircleOutline size={32}/> <a
                                                                        href="/login">Log in</a> or <a href="/register">Sign up</a> to
                                                                        create a collection
                                                                    </div>
                                                                    <div>Sign up for a free <em>Humanities
                                                                        Commons</em> account
                                                                        to save, share, remix, and download your own
                                                                        collections
                                                                        of keywords and artifacts.
                                                                    </div>
                                                                </div>
                                                                :
                                                                (!this.state.refreshCards && this.state.collections.length >= 1
                                                                    ? this.state.collections.map((c, index) => {
                                                                        return (<CollectionCard key={index}
                                                                                                card={c.node}
                                                                                                close={this.onDrawerClose}
                                                                                                refreshCollectionData={this.setRefresh}
                                                                                                history={this.props.history}
                                                                        />);
                                                                    }).concat(<CollectionCard key={uuid()}
                                                                                              addNew={true}
                                                                                              close={this.onDrawerClose}
                                                                                              refreshCollectionData={this.setRefresh}/>)
                                                                    :
                                                                    <CollectionCard key={uuid()}
                                                                                    addNew={true}
                                                                                    close={this.onDrawerClose}
                                                                                    refreshCollectionData={this.setRefresh}/>)
                                                        }
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </ReactDrawer>
                        )
                    }}
                </Query>
            </div>
        </>;

        if (!this.state.dataLoaded) {
            return "";
        }

        // check if local state refresh has been requested.
        if (this.state.refreshCards) {
            //console.log('before', this.state.collections);
            this.getData().then(() => {
                //console.log('card data updated');
                //console.log('after', this.state.collections);
                this.setState({refreshCards: false});
            });
        }

        /* this is the render's return */
        return cards({isOpen: this.state.open});
    }

}

// export default CollectionsDrawer
export default withApollo(CollectionsDrawer);
