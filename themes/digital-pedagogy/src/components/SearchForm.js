import React from 'react';
import {FaLongArrowAltRight} from "react-icons/fa";
const onSearchSubmit = (e, history) => {
    e.preventDefault();
    const data = new FormData(e.target);
    const terms = data.get('search').replace(/ /g, "+").replace(/#/g, "");
    history.push('/search/' + terms);
};

//stateless component that just renders html that forwards input to the /search page.
export const SearchForm = ({history}) => <div className='search__body__section'>
    <form onSubmit={(e) => onSearchSubmit(e, history)} className={'row search__body__section__form'}>
        <input className={'col-sm-11 search__body__section__form__text'} name="search" id="search"
               placeholder={"Try searching for topics or tags"}/>
        <button className={'col-sm-1 search__body__section__form__submit_btn'} type="submit" aria-label="Search">
            <FaLongArrowAltRight size={40}/></button>
    </form>
</div>
