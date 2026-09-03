import { TestBed } from '@angular/core/testing';

import { TokenStorageService } from './token-storage.service';

describe('TokenStorageService', () => {
  let service: TokenStorageService;

  beforeEach(() => {
    localStorage.clear();

    TestBed.configureTestingModule({
      providers: [TokenStorageService],
    });

    service = TestBed.inject(TokenStorageService);
  });

  afterEach(() => {
    localStorage.clear();
  });

  it('stores and retrieves a token', () => {
    service.setToken('test-token');

    expect(service.getToken()).toBe('test-token');
  });

  it('reports whether a token exists', () => {
    expect(service.hasToken()).toBe(false);

    service.setToken('test-token');

    expect(service.hasToken()).toBe(true);
  });

  it('clears the token', () => {
    service.setToken('test-token');

    service.clearToken();

    expect(service.getToken()).toBeNull();
    expect(service.hasToken()).toBe(false);
  });
});
