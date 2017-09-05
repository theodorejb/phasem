import {Injectable} from '@angular/core';
import {Observable} from 'rxjs/Observable';
import {LoginCredentials, NewUser} from '../models/User';
import {ApiService} from "./ApiService";

@Injectable()
export class AuthService {
    constructor (private api: ApiService) {}

    createUser(newUser: NewUser) {
        return this.api.requestBody('post', 'auth/user', newUser);
    }

    logIn(credentials: LoginCredentials) {
        return this.api.requestBody('post', 'auth/token', credentials)
            .map((resp) => {this.api.setAuth(resp.token);});
    }

    logOut(): Observable<boolean> {
        return this.api.isLoggedIn()
            .mergeMap(isLoggedIn => {
                if (isLoggedIn) {
                    return this.api.request('delete', 'auth/token').map(() => true);
                } else {
                    return Observable.of(false);
                }
            })
            .do(() => {this.api.unsetCurrentUser();});
    }
}
