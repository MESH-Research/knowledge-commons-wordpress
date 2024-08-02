//
import React from 'react';
import {Link} from "react-router-dom";
import {FaUser, FaKey, FaBars, FaFolder, FaTag, FaLink} from "react-icons/fa"; //Icons
import uuid from "uuid/v4"; //used to create unique ID's
import {
    setCacheState, // function to write to cache state
    m_deleteArtifactToCollection, // deletes an artifact (comment) from the collection
    q_collectionList, // gets a collection and it's artifacts
    m_addCollection, // adds a new collection per user
    m_addArtifactToCollection // adds an artifact (comment) to the collection record
} from "../graphql/bin/queries";
import readCache from "../graphql/queries/Cache_ReadState.graphql";
import {Query} from "react-apollo";
import ScrollableAnchor, {configureAnchors} from "./mla-scrollable-anchor";

class Card extends React.Component {
    constructor(props) {
        super(props); // inherit the parent constructor
        this._isMounted = false;
        this.state = { // default state
            refreshCD: true,
            collections: false,
            is_open: !!this.props.open,
            isKeywordPage: !!this.props.isKeywordPage,
            is_keyword: this.props.data.node.dp_genres.edges[0].node.name.toLowerCase() === "curation statement",
            is_hidden: !!this.props.hidden,
            is_dragging: false,
            isLoading: true,
            data: this.props.data.node,
            addToCollectionValue: null,
            results: false
        };

        configureAnchors({offset: -200, scrollDuration: 200, keepLastAnchorHash: true, scrollUrlHashUpdate: false});
        // we need to bind the class functions to ensure they have access to the 'this' variable
        this.onClick = this.onClick.bind(this);
        this.removeArtifactFromCollection = this.removeArtifactFromCollection.bind(this);
    }

    componentDidMount() {
        this._isMounted = true;
        if(this._isMounted) {
            this.state.refreshCD && this.getData();
        }
    }

    componentDidUpdate(prevProps, prevState, snapshot) {
        if(this._isMounted) {
            this.state.refreshCD && this.getData();
        }
    }

    /*
        The getData method pulls in the data from the associated queries, formats (where applicable) and writes data to state.
    */
    getData() {
        return q_collectionList(window.dpVars.userId).then(results => {
            this.setState({
                results: results,
                collections: results.data.collections.edges,
                refreshCD: false,
                isLoading: false
            });
        }).catch(function (e) {
            console.log("Promise Rejected get_data()");
            console.log(JSON.stringify(e));
        });
    }

    /*
        Used on the 'add to collection' button -- which is actually a style selector.
    */
    onSelectAddCollection(e) {
        e.preventDefault();
        const val = e.target.value;
        if (val === "new") {
            this.addNewCollectionAndArtifact();
        } else {
            this.addArtifactToCollection(val);
        }
        this.setState({refreshCD: true, addToCollectionValue: null});
    }

    /*
        Wrapper method to call the m_addCollection query which creates new user collections.
    */
    addNewCollectionAndArtifact() {
        if (window.dpVars.userId == false) {
            return false;
        }
        m_addCollection(window.dpVars.userId).then(results => {
            if (results) {
                setCacheState({refreshDrawer: "yes"});
                this.addArtifactToCollection(results.data.createCollection.collection.collectionId)
            }
        }).catch(function () {
            console.log("Promise Rejected addNewCollectionAndArtifact");
        });
    }

    /*
        Wrapper method to call the m_deleteArtifactToCollection query which removes artifacts from collections.
    */
    removeArtifactFromCollection() {
        const commentObject = this.state.collections[0].node.author.comments.nodes.filter((commentObj) => {
            return this.state.data.artifactId === parseInt(commentObj.content)
        });
        m_deleteArtifactToCollection(commentObject[0].id).then(results => {
            const title = this.state.data.title.replace(/ /g, "-").replace(/[.,\/#!$%\^&\*;:{}=_'’`“”~()\?]/g, "").replace(/\s{2,}/g, " ").toLowerCase();
            let el = document.getElementById(title);
            el.remove();
            setCacheState({refreshCollectionPageData: "yes", refreshDrawer: "yes"});
        }).catch(function (e) {
            console.log("Promise Rejected removeArtifactToCollection");
            console.log(e);
        });
    };

    /*
        Wrapper method to call the m_addArtifactToCollection query which adds artifacts from collections.
        Returns alert box if new artifact is a duplicate.
    */
    addArtifactToCollection(collectionId) {
        document.querySelector('.collection_button').classList.add('added');
        // console.log(this.state.data.artifactId);
        // console.log(collectionId);
        m_addArtifactToCollection(this.state.data.artifactId, collectionId).then(results => {
            const rData = results.data;
            //console.log("results: ", results);
            document.querySelector('.collection_button').classList.remove('added');

            if (!rData || !rData.createComment) {
                alert('Duplicate Content\nLooks like you\'ve already added that to a collection.');
            } else {
                this.getData();
            }
            setCacheState({refreshDrawer: "yes"});
        }).catch(function (e) {
            console.log("Promise Rejected addArtifactToCollection");
            console.log(e);
        });
    }

    // on Drop in case the user lets go of card, is Dragging should be set to 'no' again.
    onDrop(ev) {
        ev.preventDefault();
        setCacheState({isDragging: "no"});
    }

    // Drawer open button
    onClick(e) {
        e.preventDefault();
        this.setState((prevState) => {
            return {is_open: !prevState.is_open}
        });
    }

    // Drag Start event. Will open the drawer if it's closed.
    // We use cache state here because it's tracking state over multiple components.
    dragStart(e, id) {
        setCacheState({isDragging: "yes"}).then((res) => {

            this.setState({is_dragging: true});
        });
        e.dataTransfer.setData("text", id);
        const img = document.createElement('img');
        //img.src = require('../styles/images/drag-graphic.gif');
        img.src = 'https://imagizer.imageshack.com/img923/1623/flhETq.gif';
        e.dataTransfer.setDragImage(img, 0, 0);


    }

    // Drag end event. Will close the drawer ONLY if it's been opened by Drag Start event.
    // We use cache state here because it's tracking state over multiple components.
    dragEnd(e) {
        setCacheState({isDragging: "no"});
    }

    dragImage() {

    }

    addToCollectionHTML = () => {
        const collectionList = this.state.collections;
        const is_collection = !!this.props.collection_id;
        if (this.state.isLoading || window.dpVars.userId == false) {
            return false;
        }
        if (is_collection && this.props.isCollectionOwner) {
            return (<button onClick={this.removeArtifactFromCollection}
                            className="artifact__body__wrapper--remove-from-collection__button"
                            aria-label="Remove from Collection">REMOVE FROM
                COLLECTION</button>);
        } else {
            return (
                <select value={this.state.addToCollectionValue ? this.state.addToCollectionValue : ""}
                        onChange={(e) => this.onSelectAddCollection(e)}
                        className="artifact__body__wrapper--add-to-collection__select" aria-label="Add to Collection">
                    <option> ADD TO COLLECTION</option>
                    <option value={"new"}>... NEW COLLECTION</option>
                    {collectionList.map((obj, index) => {
                        //console.log(obj.node);
                        let alreadyCollected = false;
                        const BreakException = {};

                        // Do not list a collection the artifact is already in:
                        try {
                            obj.node.artifacts.edges.forEach((artifact) => {
                                if (artifact.node.artifactId === this.state.data.artifactId) {
                                    alreadyCollected = true;
                                    throw BreakException;
                                }
                            });
                        } catch (e) {
                            if (e !== BreakException) throw e;
                        }

                        if (obj.node.title !== "" && alreadyCollected === false) {
                            return (
                                <option className={'add-collection-item'} key={index}
                                        value={obj.node.collectionId}>{obj.node.title}</option>
                            );
                        }
                    })}
                </select>
            );
        }
    };


    // HTML rendered to screen.
    // uses pure HTML 5 drag and drop api.
    // Everything inside the first div (of return) is written in JSX.
    render() {
        const hidden_class = this.state.is_hidden ? " hidden " : ' ';
        const keyword_class = this.state.is_keyword ? " keyword " : '';
        const opened_class = this.state.is_open ? " col-xl-12 " : " col-xl-4 col-12 col-md-6 ";
        const show_class = this.state.is_open ? " open" : '';
        const article_id = this.state.data.title.replace(/ /g, "-").replace(/[.,\/#!$%\^&\*;:{}=_'’`“”~()\?]/g, "").replace(/\s{2,}/g, " ").toLowerCase();
        const dragClass = this.state.data.dp_keywords.edges[0].node.name.toLowerCase() !== "introduction" && this.state.is_dragging ? " dragging " : '';
        const getLastTag = this.state.data.tags && this.state.data.tags.edges.length > 1 && this.state.data.tags.edges[this.state.data.tags.edges.length - 1].node.name;
        const introClass = this.state.data.dp_keywords.edges[0].node.name.toLowerCase() == 'introduction' ? 'introduction' : '';
        return (
            <ScrollableAnchor id={`${article_id}`}>
                <div
                    id={`${article_id}`}
                    className={` artifact${hidden_class}${keyword_class} ${opened_class} ${dragClass} ${this.props.class} ${introClass}`}>

                    <div className={` artifact__body${show_class}`}>

                        <div className={`${this.state.is_open && "row"} artifact__body__wrapper${show_class}`}>
                            {this.state.data.dp_keywords.edges[0].node.name.toLowerCase() !== "introduction" && <div
                                className={`artifact__body__wrapper__image__and__meta ${this.state.is_open ? "col-sm-12 col-md-4 order-md-1 card-open" : "col-12 order-md-0"} `}>
                                <Query query={readCache}>
                                    {
                                        ({loading, error, data}) => {
                                            if (data.appState.refreshDrawer === "yes") {
                                                setCacheState({refreshDrawer: "no"}).then(() => {
                                                    this.setState({refreshCD: true});
                                                });
                                            }

                                            return (
                                                <div className={'col-12 artifact__body__wrapper--add-to-collection'}>
                                                    {this.addToCollectionHTML()}
                                                </div>
                                            );
                                        }
                                    }
                                </Query>
                                {
                                    this.state.data.featuredImage && <img
                                        className='middle-center w-100'
                                        src={`${this.state.data.featuredImage && this.state.data.featuredImage.sourceUrl}`}/>
                                }

                                <div className='col-12 artifact__body__wrapper__author'>
                                    {this.state.data.dp_authors && this.state.data.dp_authors.edges && this.state.data.dp_authors.edges.map((obj, index) => {
                                        let author_name = obj.node.name;
                                        let author_role = '';
                                        if (obj.node.role) {
                                            obj.node.role.forEach(f_role => {
                                                if (f_role && f_role.includes(':')) {
                                                    let cur_role = f_role.split(':');
                                                    if (!author_role && cur_role[1] == this.state.data.artifactId) {
                                                        author_role = cur_role[0];
                                                    }
                                                }
                                            });
                                        }
                                        let author_org = obj.node.affiliation;
                                        let author_website = obj.node.website;
                                        return (

                                            <div key={uuid()}><FaUser key={uuid()}/>
                                                {author_website ?
                                                    <a href={author_website}>{author_name}</a> : author_name}{author_role && ", " + author_role}{author_org && <>,<i> {author_org}</i></>}
                                            </div>
                                        );
                                    })}
                                </div>
                                {
                                    !this.state.isKeywordPage && this.state.data.dp_keywords && this.state.data.dp_keywords.edges[0] &&
                                    <div className='artifact__body__meta__field artifact__body__meta__keyword'>
                                        <FaKey/><span className={'ml-2'}>{
                                        <Link key={uuid()}
                                              to={`/keyword/${this.state.data.dp_keywords.edges[0].node.name}`}>{this.state.data.dp_keywords.edges[0].node.name}</Link>}</span>
                                    </div>
                                }
                                {
                                    this.state.data.dp_genres && this.state.data.dp_genres.edges[0] &&
                                    <div className='artifact__body__meta__field artifact__body__meta__genre'>
                                        <FaFolder/><span className={'ml-2'}>{
                                        <Link key={uuid()}
                                              to={`/search/type/${this.state.data.dp_genres.edges[0].node.name.replace("-", '\u2014')}`}>{this.state.data.dp_genres.edges[0].node.name}</Link>}</span>
                                    </div>
                                }
                                {
                                    this.state.data.tags && this.state.data.tags.edges[0] &&
                                    <div className='artifact__body__meta__field artifact__body__meta__tags'>
                                        <FaTag/><span
                                        className={'ml-2'}>{this.state.data.tags.edges.map((obj, index) => {
                                        return (<Link key={index}
                                                      to={`/search/tag/${obj.node.name.replace("-", '\u2014')}`}>{obj.node.name}{getLastTag && getLastTag !== obj.node.name && ", "}</Link>)
                                    })}</span>
                                    </div>
                                }
                                {
                                    this.state.data.sources &&
                                    <div className='artifact__body__meta__field artifact__body__meta__link'>
                                        <FaLink/><span className={'ml-2 source-link'}
                                                       dangerouslySetInnerHTML={{__html: this.state.data.sources}}/>
                                    </div>
                                }
                                {
                                    this.state.data.core &&
                                    <div className='artifact__body__meta__field artifact__body__meta__download'>
                                        <span className="HCCircle">HC</span><span className={'ml-1'}
                                                                                  dangerouslySetInnerHTML={{__html: this.state.data.core}}/>
                                    </div>
                                }
                            </div>
                            }
                            <div
                                className={`artifact__body__wrapper__title__and__content ${this.state.is_open ? this.state.data.dp_keywords.edges[0].node.name.toLowerCase() !== "introduction" ? "col-md-8 col-sm-12" : "col-12" + " order-md-0 card-open" : "col-12 order-md-1"} `}>
                                {this.state.data.dp_keywords.edges[0].node.name.toLowerCase() !== "introduction" &&
                                <div draggable="true"
                                     onDragStart={e => this.dragStart(e, this.state.data.artifactId)}
                                     onDragEnd={e => this.dragEnd(e, this.state.data.artifactId)}
                                     className={`artifact__body__wrapper--handle  ${dragClass}`}><span><FaBars
                                    size={24}/></span></div>}

                                <div className={'col-12 artifact__body__wrapper__title'}>


                                    {this.state.data.dp_keywords && this.state.data.dp_keywords.edges[0].node.name.toLowerCase().trim() === this.state.data.title.toLowerCase().trim() ?
                                        <h4>Keyword
                                            Name</h4> : this.state.data.dp_keywords.edges[0].node.name.toLowerCase() !== "introduction" ?
                                            <h4>Artifact Name</h4> : <h4>Page</h4>}
                                    <h3
                                        dangerouslySetInnerHTML={{__html: this.state.data.title}}/>
                                </div>
                                { this.state.data.dp_keywords.edges[0].node.name.toLowerCase() !== "introduction" &&
                                  <div className='col-12 artifact__body__wrapper__author'>
                                      <FaUser/>
                                      <span className={'ml-2'}>
                                        {(this.state.data.dp_authors && this.state.data.dp_authors.edges.length > 2) ? this.state.data.dp_authors.edges[0].node.name + ', ' + this.state.data.dp_authors.edges[1].node.name + ', et al.' : ''  }
                                        {(this.state.data.dp_authors && this.state.data.dp_authors.edges.length === 2) ? this.state.data.dp_authors.edges[0].node.name + ' and ' + this.state.data.dp_authors.edges[1].node.name : ''  }
                                        {(this.state.data.dp_authors && this.state.data.dp_authors.edges.length < 2 ) ? this.state.data.dp_authors.edges[0].node.name  : ''  }
                                      </span>
                                  </div>
                                }
                                {this.state.data.dp_keywords.edges[0].node.name.toLowerCase() !== "introduction" &&
                                <div className={'col-12 artifact__body__wrapper--add-to-collection'}>
                                    {this.addToCollectionHTML()}
                                </div>
                                }
                                <div className={'col-12 artifact__body__wrapper__content'}>
                                    {this.state.data.dp_keywords.edges[0].node.name.toLowerCase() !== "introduction" &&
                                    <h4>Curatorial note</h4>}
                                    {this.state.is_open ?
                                        <div dangerouslySetInnerHTML={{__html: this.state.data.content}}/>
                                        : this.state.data.dp_keywords.edges[0].node.name.toLowerCase() !== "introduction" ?
                                            <div
                                                dangerouslySetInnerHTML={{__html: this.state.data.content.split(" ").splice(0, 17).join(" ") + "..."}}/> :
                                            <div
                                                dangerouslySetInnerHTML={{__html: this.state.data.content.split(" ").splice(0, 35).join(" ") + "..."}}/>}
                                    {!!this.state.data.curator &&
                                    <p className={'text-right curator-name'}>—{this.state.data.curator}</p>}
                                </div>

                            </div>


                        </div>

                        <div className={'col-12 middle-center artifact__body__wrapper--readmore order-md-2 text-center'}>
                            {this.state.data.dp_keywords.edges[0].node.name.toLowerCase() !== "introduction" &&
                                <button onClick={this.onClick}
                                        className='artifact__body__button artifact__body__button--open'>
                                    {this.state.is_open ? "Read less" : "Read more"}
                                </button>
                            }
                            {this.state.data.dp_keywords.edges[0].node.name.toLowerCase() === "introduction" &&
                                <p className='artifact__body__button artifact__body__button--intro'>
                                    {this.state.data.dp_keywords.edges[0].node.name.toLowerCase() === "introduction" &&
                                    <Link key={uuid()} to={"/"+this.state.data.uri}>Read More</Link>}
                                </p>
                            }
                        </div>

                    </div>

                </div>
            </ScrollableAnchor>
        );
    }
}

export default Card;
